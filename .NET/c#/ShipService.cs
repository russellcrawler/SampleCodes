using Microsoft.EntityFrameworkCore;
using MoreLinq;
using OfficeOpenXml.FormulaParsing.Excel.Functions.DateTime;
using SCMSCoreAPI.Helpers;
using SCMSCoreAPI.Models;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using AutoMapper;
using Microsoft.Extensions.Options;
using System.IO;
using System.Drawing.Imaging;
using System.Drawing;

namespace SCMSCoreAPI.Services
{
    public interface IShipService
    {
        IEnumerable<Ship> GetAll();
        Ship GetById(int id, DeckListRequestBase request);
        Drawing GetDeckById(int id);
        Ship Create(Ship ship);
        Ship UpdateShip(int id, Ship ship);
        Ship UpdateShipStatus(int id, statusBase ship);
        Drawing UpdateDeck(Drawing drawing);
        void DeleteShip(int id);
        void DeleteDeck(int id);
    }

    public class ShipService : IShipService
    {
        private SCMSContext _context;
        private readonly AppSettings _appSettings;
        private IMapper _mapper;

        public ShipService(SCMSContext context, IMapper mapper, IOptions<AppSettings> appSettings)
        {
            _context = context;
            _mapper = mapper;
            _appSettings = appSettings.Value;
        }

        /// <summary>
        /// method using by API endpoint: /api/ships/list
        /// Use: get list of ships from internal database
        /// Input: None
        /// </summary>
        /// <returns>Object</returns>
        public IEnumerable<Ship> GetAll()
        {
            var ships = _context.Ship
                //.Include(x => x.Drawing)
                //.Include(x => x.FrameofZones)
                .Where(x => x.VopShipId != null && x.IsDeleted == false)
                .OrderBy(x => x.Name).ToList();

            foreach (Ship s in ships)
            {
                s.Drawing = _context.Drawing.Where(x => x.ShipId == s.ShipId && x.IsDeleted == false).ToList();
            }

            return ships;
        }

        /// <summary>
        /// method using by API endpoint: /api/ships/{id}
        /// Use: get single ship from internal database
        /// Input: ship id
        /// </summary>
        /// <param name="shipid"></param>
        /// <param name="deckRequest"></param>
        /// <returns>Object</returns>
        public Ship GetById(int id, DeckListRequestBase request)
        {
            // return user info based on ID
            var ship = _context.Ship
                .Include(s => s.Client)
                .Where(m => m.ShipId == id)
                .FirstOrDefault();

            if (request.AllDecks == null || request.WithoutZones == null || request.WithoutCenter == null)
            {
                throw new AppException("One of request parameter is missing.");
            }
            else if (request.AllDecks == 0 && request.WithoutZones == 0 && request.WithoutCenter == 0)
            {
                throw new AppException("One of request parameter must be true.");
            }

            // return all decks of selected ship
            List<Drawing> decks = _context.Drawing
                .Where(x => x.ShipId==ship.ShipId && x.IsDeleted == false)
                .ToList();


            if (request.WithoutZones == 1)
            {
                // first get zone information of each deck
                foreach (Drawing d in decks)
                {
                    // add active zones to deck
                    d.Zones = _context.Zones.Where(x => x.DrawingId == d.DrawingId && x.IsDeleted == false).ToList();

                    foreach (Zones z in d.Zones)
                    {
                        // add active zonemaster to zone
                        z.ZoneMaster = _context.ZoneMaster.Where(x => x.ZoneId == z.ZoneId && x.IsDeleted == false).ToList();
                    }
                }

                var _decks = decks;

                // return only decks which does not have any defined zone yet
                _decks = decks.Where(x => x.Zones.Where(z => z.ZoneMaster.Count()>0).Count()==0).ToList();
                //decks = _context.Drawing
                //.Include(z => z.Zones)
                //.ThenInclude(z => z.ZoneMaster)
                //.Where(x => x.ShipId==ship.ShipId && x.IsDeleted == false 
                //    && !_context.Zones.Where(z => z.DrawingId == x.DrawingId && z.IsDeleted == false 
                //        && z.ZoneMaster.Where(m => m.IsDeleted == false).Count()>0).Any())
                //.ToList();
                decks = _decks;
            }
            else if (request.WithoutCenter == 1)
            {
                // return only decks which does not have any defined center points yet
                decks = _context.Drawing
                //.Include(z => z.Zones)
                .Where(x => x.ShipId == ship.ShipId && x.IsDeleted == false && !_context.DeckCenter.Where(z => z.DeckId == x.DrawingId && z.IsDeleted == false).Any())
                .ToList();
            }

            foreach (Drawing deck in decks)
            {
                deck.Zones = new List<Zones>();
            }

            ship.Drawing = decks;

            if (ship.Drawing.Count()==0)
            {
                //throw new AppException("No deck found in selected ship.");
            }

            return ship;
        }


        /// <summary>
        /// method using by API endpoint: /api/ships/{id}
        /// Use: get single ship from internal database
        /// Input: ship id
        /// </summary>
        /// <param name="shipid"></param>
        /// <param name="deckRequest"></param>
        /// <returns>Object</returns>
        public Drawing GetDeckById(int id)
        {
            // return user info based on ID
            var deck = _context.Drawing
                .Where(m => m.DrawingId == id)
                .FirstOrDefault();

            return deck;
        }

        /// <summary>
        /// method using by API endpoint: /api/ship/create
        /// Use: create new ship 
        /// Input: ship information
        /// </summary>
        /// <param name="shipBase"></param>
        /// <returns>Object</returns>
        public Ship Create(Ship shipBase)
        {
            return new Ship();
        }

        /// <summary>
        /// method using by API endpoint: /api/ship/update/{id}
        /// Use: update ship information
        /// Input: ship information
        /// </summary>
        /// <param name="id"></param>
        /// <param name="shipParam"></param>
        /// <returns>Object</returns>
        public Ship UpdateShip(int id, Ship shipParam)
        {
            Ship ship = _context.Ship.Find(id);

            ship.Name = shipParam.Name;
            if(shipParam.FileName != null)
            {
                ship.FileName = shipParam.FileName;
            }
            ship.Comments = shipParam.Comments;
            ship.IsActive = shipParam.IsActive;
            if(shipParam.ImagePath != null)
            {
                //ship.ImagePath = shipParam.ImagePath;
                // if new image is uploading then remove existing one first
                var existingImage = Directory.GetCurrentDirectory().ToString() + _appSettings.ShipImagePath + ship.ShipId.ToString() + "\\" + ship.FileName;
                if (File.Exists(existingImage)) File.Delete(existingImage);

                // uplaod new image to folder
                var newImagePath = Directory.GetCurrentDirectory().ToString() + _appSettings.ShipImagePath + ship.ShipId.ToString();

                if (!Directory.Exists(newImagePath)) Directory.CreateDirectory(newImagePath);

                ImageFormat iformat = ImageFormat.Jpeg;
                if (shipParam.ImageType == "png")
                {
                    iformat = ImageFormat.Png;
                }
                else if (shipParam.ImageType == "bmp")
                {
                    iformat = ImageFormat.Bmp;
                }
                else if (shipParam.ImageType == "gif")
                {
                    iformat = ImageFormat.Gif;
                }
                else if (shipParam.ImageType == "tiff")
                {
                    iformat = ImageFormat.Tiff;
                }

                try
                {
                    var newImageDataByteArray = Convert.FromBase64String(shipParam.ImagePath);
                    var newImageDataStream = new MemoryStream(newImageDataByteArray) { Position = 0 };
                    var newImage = new Bitmap(Image.FromStream(newImageDataStream));
                    newImage.Save(newImagePath + "\\" + shipParam.FileName, iformat);
                    newImage.Dispose();
                    ship.ImagePath = _appSettings.MediaURL + "Ships/" + ship.ShipId.ToString() + "/";
                }
                catch (Exception ex)
                { }
            }

            _context.Ship.Update(ship);
            _context.SaveChanges();

            foreach (Drawing deckParam in shipParam.Drawing)
            {
                if (Convert.ToInt32(deckParam.DrawingId) > 0)
                {
                    var deckUpdate = _context.Drawing.Find(deckParam.DrawingId);

                    if (deckUpdate != null)
                    {
                        deckUpdate.Name = deckParam.Name;
                        if(deckParam.FileName != null)
                        {
                            deckUpdate.FileName = deckParam.FileName;
                        }
                        deckUpdate.Height = deckParam.Height;
                        deckUpdate.Width = deckParam.Width;
                        deckUpdate.TopX = deckParam.TopX;
                        deckUpdate.TopY = deckParam.TopY;
                        deckUpdate.BottomX = deckParam.BottomX;
                        deckUpdate.BottomY = deckParam.BottomY;
                        if(deckParam.ImagePath != null)
                        {
                            // if new image is uploading then remove existing one first
                            var existingImage = Directory.GetCurrentDirectory().ToString() + _appSettings.DeckImagePath + deckUpdate.DrawingId.ToString() + "\\" + deckUpdate.FileName;
                            if (File.Exists(existingImage)) File.Delete(existingImage);

                            // uplaod new image to folder
                            var newImagePath = Directory.GetCurrentDirectory().ToString() + _appSettings.DeckImagePath + deckUpdate.DrawingId.ToString();

                            if (!Directory.Exists(newImagePath)) Directory.CreateDirectory(newImagePath);

                            ImageFormat iformat = ImageFormat.Jpeg;
                            if (deckParam.ImageType == "png")
                            {
                                iformat = ImageFormat.Png;
                            }
                            else if (deckParam.ImageType == "bmp")
                            {
                                iformat = ImageFormat.Bmp;
                            }
                            else if (deckParam.ImageType == "gif")
                            {
                                iformat = ImageFormat.Gif;
                            }
                            else if (deckParam.ImageType == "tiff")
                            {
                                iformat = ImageFormat.Tiff;
                            }

                            try
                            {
                                var newImageDataByteArray = Convert.FromBase64String(deckParam.ImagePath);
                                var newImageDataStream = new MemoryStream(newImageDataByteArray) { Position = 0 };
                                var newImage = new Bitmap(Image.FromStream(newImageDataStream));
                                newImage.Save(newImagePath + "\\" + deckParam.FileName, iformat);
                                newImage.Dispose();
                                deckUpdate.ImagePath = _appSettings.MediaURL + "Decks/" + deckUpdate.DrawingId.ToString() + "/";
                            }
                            catch (Exception ex)
                            { }
                        }
                        if (deckParam.ScanResult != null)
                        {
                            deckUpdate.ScanResult = deckParam.ScanResult;
                        }

                        _context.Drawing.Update(deckUpdate);
                        _context.SaveChanges();
                    }
                }
                else
                {

                    var deckNew = new Drawing()
                    {
                        ShipId = id,
                        Name = deckParam.Name,
                        FileName = deckParam.FileName,
                        Height = deckParam.Height,
                        Width = deckParam.Width,
                        TopX = deckParam.TopX,
                        TopY = deckParam.TopY,
                        BottomX = deckParam.BottomX,
                        BottomY = deckParam.BottomY,
                        ImagePath = null,
                        ScanResult = deckParam.ScanResult,
                    };
                    _context.Drawing.Add(deckNew);
                    _context.SaveChanges();

                    // uplaod new image to folder
                    var newImagePath = Directory.GetCurrentDirectory().ToString() + _appSettings.DeckImagePath + deckNew.DrawingId.ToString();

                    if (!Directory.Exists(newImagePath)) Directory.CreateDirectory(newImagePath);

                    ImageFormat iformat = ImageFormat.Jpeg;
                    if (deckParam.ImageType == "png")
                    {
                        iformat = ImageFormat.Png;
                    }
                    else if (deckParam.ImageType == "bmp")
                    {
                        iformat = ImageFormat.Bmp;
                    }
                    else if (deckParam.ImageType == "gif")
                    {
                        iformat = ImageFormat.Gif;
                    }
                    else if (deckParam.ImageType == "tiff")
                    {
                        iformat = ImageFormat.Tiff;
                    }

                    try
                    {
                        var newImageDataByteArray = Convert.FromBase64String(deckParam.ImagePath);
                        var newImageDataStream = new MemoryStream(newImageDataByteArray) { Position = 0 };
                        var newImage = new Bitmap(Image.FromStream(newImageDataStream));
                        newImage.Save(newImagePath + "\\" + deckParam.FileName, iformat);
                        newImage.Dispose();
                        deckNew.ImagePath = _appSettings.MediaURL + "Decks/" + deckNew.DrawingId.ToString() + "/";

                        _context.Drawing.Update(deckNew);
                        _context.SaveChanges();
                    }
                    catch (Exception ex)
                    { }
                }
            }

            return GetById(Convert.ToInt32(ship.ShipId), new DeckListRequestBase { AllDecks = 1, WithoutCenter = 0, WithoutZones = 0 });
        }

        /// <summary>
        /// method using by API endpoint: /api/ship/update/status/{id}
        /// Use: update ship information
        /// Input: ship status information
        /// </summary>
        /// <param name="id"></param>
        /// <param name="status"></param>
        /// <returns>Object</returns>
        public Ship UpdateShipStatus(int id, statusBase shipParam)
        {
            Ship ship = _context.Ship.Find(id);

            ship.IsActive = Convert.ToBoolean(shipParam.status);

            _context.Ship.Update(ship);
            _context.SaveChanges();

            return ship;
        }

        /// <summary>
        /// method using by API endpoint: /api/ships/deck/update/{id}
        /// Use: update deck information
        /// Input: deck information
        /// </summary>
        /// <param name="deckParam"></param>
        /// <returns>Object</returns>
        public Drawing UpdateDeck(Drawing deckParam)
        {
            if (Convert.ToInt32(deckParam.DrawingId) > 0)
            {
                var deckUpdate = _context.Drawing.Find(deckParam.DrawingId);

                if (deckUpdate != null)
                {
                    deckUpdate.Name = deckParam.Name;
                    if (deckParam.FileName != null)
                    {
                        deckUpdate.FileName = deckParam.FileName;
                    }
                    deckUpdate.Height = deckParam.Height;
                    deckUpdate.Width = deckParam.Width;
                    deckUpdate.TopX = deckParam.TopX;
                    deckUpdate.TopY = deckParam.TopY;
                    deckUpdate.BottomX = deckParam.BottomX;
                    deckUpdate.BottomY = deckParam.BottomY;
                    deckUpdate.ShipId = deckParam.ShipId;

                    _context.Drawing.Update(deckUpdate);
                    _context.SaveChanges();

                    if (deckParam.ImagePath != null)
                    {
                        // if new image is uploading then remove existing one first
                        var existingImage = Directory.GetCurrentDirectory().ToString() + _appSettings.DeckImagePath + deckUpdate.DrawingId.ToString() + "\\" + deckUpdate.FileName;
                        if (File.Exists(existingImage)) File.Delete(existingImage);

                        // uplaod new image to folder
                        var newImagePath = Directory.GetCurrentDirectory().ToString() + _appSettings.DeckImagePath + deckUpdate.DrawingId.ToString();

                        if (!Directory.Exists(newImagePath)) Directory.CreateDirectory(newImagePath);

                        ImageFormat iformat = ImageFormat.Jpeg;
                        if (deckParam.ImageType == "png")
                        {
                            iformat = ImageFormat.Png;
                        }
                        else if (deckParam.ImageType == "bmp")
                        {
                            iformat = ImageFormat.Bmp;
                        }
                        else if (deckParam.ImageType == "gif")
                        {
                            iformat = ImageFormat.Gif;
                        }
                        else if (deckParam.ImageType == "tiff")
                        {
                            iformat = ImageFormat.Tiff;
                        }

                        if (deckParam.ScanResult != null)
                        {
                            deckUpdate.ScanResult = deckParam.ScanResult;
                        }


                        try
                        {
                            var newImageDataByteArray = Convert.FromBase64String(deckParam.ImagePath);
                            var newImageDataStream = new MemoryStream(newImageDataByteArray) { Position = 0 };
                            var newImage = new Bitmap(Image.FromStream(newImageDataStream));
                            newImage.Save(newImagePath + "\\" + deckParam.FileName, iformat);
                            newImage.Dispose();
                            deckUpdate.ImageType = deckParam.ImageType;
                            deckUpdate.ImagePath = _appSettings.MediaURL + "Decks/" + deckUpdate.DrawingId.ToString() + "/";

                            _context.Drawing.Update(deckUpdate);
                            _context.SaveChanges();
                        }
                        catch (Exception ex)
                        { }
                    }

                    deckParam = deckUpdate;
                }
                else
                {
                    throw new AppException("Deck not found.");
                }
            }
            else
            {
                var deckNew = new Drawing()
                {
                    ShipId = deckParam.ShipId,
                    Name = deckParam.Name,
                    FileName = deckParam.FileName,
                    Height = deckParam.Height,
                    Width = deckParam.Width,
                    TopX = deckParam.TopX,
                    TopY = deckParam.TopY,
                    BottomX = deckParam.BottomX,
                    BottomY = deckParam.BottomY,
                    ImagePath = null,
                    ScanResult = deckParam.ScanResult,
                    ImageType = deckParam.ImageType
                };
                _context.Drawing.Add(deckNew);
                _context.SaveChanges();


                // uplaod new image to folder
                var newImagePath = Directory.GetCurrentDirectory().ToString() + _appSettings.DeckImagePath + deckNew.DrawingId.ToString();

                if (!Directory.Exists(newImagePath)) Directory.CreateDirectory(newImagePath);

                ImageFormat iformat = ImageFormat.Jpeg;
                if (deckParam.ImageType == "png")
                {
                    iformat = ImageFormat.Png;
                }
                else if (deckParam.ImageType == "bmp")
                {
                    iformat = ImageFormat.Bmp;
                }
                else if (deckParam.ImageType == "gif")
                {
                    iformat = ImageFormat.Gif;
                }
                else if (deckParam.ImageType == "tiff")
                {
                    iformat = ImageFormat.Tiff;
                }

                try
                {
                    var newImageDataByteArray = Convert.FromBase64String(deckParam.ImagePath);
                    var newImageDataStream = new MemoryStream(newImageDataByteArray) { Position = 0 };
                    var newImage = new Bitmap(Image.FromStream(newImageDataStream));
                    newImage.Save(newImagePath + "\\" + deckParam.FileName, iformat);
                    newImage.Dispose();
                    deckNew.ImagePath = _appSettings.MediaURL + "Decks/" + deckNew.DrawingId.ToString() + "/";

                    _context.Drawing.Update(deckNew);
                    _context.SaveChanges();

                    deckParam = deckNew;

                }
                catch (Exception ex)
                { }
            }

            return deckParam;
        }

        /// <summary>
        /// method using by API endpoint: /api/ships/delete/{id}
        /// Use: delete ship information
        /// Input: ship id
        /// </summary>
        /// <param name="id"></param>
        public void DeleteShip(int id)
        {
            var ship = _context.Ship.Find(id);
            if (ship != null)
            {
                //ship.VopShipId = null;
                ship.IsDeleted = true;
                _context.Ship.Update(ship);

                var decks = _context.Drawing.Where(x => x.ShipId == ship.ShipId).ToList();
                foreach (Drawing deck in decks)
                {
                    deck.IsDeleted = true;
                    _context.Drawing.Update(deck);
                }
                
                _context.SaveChanges();
            }
            _context.Dispose();
        }

        /// <summary>
        /// method using by API endpoint: /api/ships/deck/delete/{id}
        /// Use: delete deck information
        /// Input: deck id
        /// </summary>
        /// <param name="id"></param>
        public void DeleteDeck(int id)
        {
            var deck = _context.Drawing.Find(id);
            if (deck != null)
            {
                deck.IsDeleted = true;
                _context.Drawing.Update(deck);
                _context.SaveChanges();
            }
            _context.Dispose();
        }
    }
}
