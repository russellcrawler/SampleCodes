using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;

using SCMSCoreAPI.Models;
using SCMSCoreAPI.Helpers;
using SCMSCoreAPI.Services;
using Microsoft.Extensions.Options;
using Newtonsoft.Json;

namespace SCMSCoreAPI.Controllers
{
    [Route("api/[controller]")]
    [ApiController]
    public class ShipsController : ControllerBase
    {
        private readonly IShipService _shipService;
        private readonly AppSettings _appSettings;
        private readonly IVopService _vopService;

        public ShipsController(IShipService shipService,
        IVopService vopService,
        IOptions<AppSettings> appSettings)
        {
            _shipService = shipService;
            _vopService = vopService;
            _appSettings = appSettings.Value;
        }

        /// <summary>
        /// POST : /api/ships/list
        /// Use: get list of all ships
        /// </summary>
        /// <returns>JSON</returns>
        [HttpGet("list")]
        public IActionResult GetAll()
        {
            try
            {
                var zones = _shipService.GetAll();
                return Ok(new
                {
                    data = zones,
                    message = "Successfully returned zone list."
                });
            }
            catch (AppException ex)
            {
                return BadRequest(new { error = ex.Message });
            }
        }


        /// <summary>
        /// API endpoint: /api/ships/{id}
        /// Use: get ship info by id
        /// </summary>
        /// <param name="id"></param>
        /// <returns>JSON</returns>
        [HttpPost("{id}")]
        public IActionResult GetById(int id, [FromBody] DeckListRequestBase request)
        {
            try
            {
                var zone = _shipService.GetById(id, request);
                var msg = "Successfully returned the ship along with all decks ";
                if (request.WithoutZones == 1) msg += " not having defined zones";
                if (request.WithoutCenter == 1) msg += " not having defined center points";

                return Ok(new
                {
                    data = zone,
                    message = msg
                });
            }
            catch (AppException ex)
            {
                return BadRequest(new { error = ex.Message });
            }
        }


        /// <summary>
        /// PUT : /api/ships/update/{id}
        /// Use: update ships information along with deck
        /// </summary>
        /// <returns>JSON</returns>
        [HttpPut("update/{id}")]
        public IActionResult UpdateShip(int id, [FromBody] Ship ship)
        {
            try
            {
                var _ship = _shipService.UpdateShip(id, ship);
                return Ok(new
                {
                    data = _ship,
                    message = "Successfully updated ship information."
                });
            }
            catch (AppException ex)
            {
                return BadRequest(new { error = ex.Message });
            }
        }


        /// <summary>
        /// PUT : /api/ships/status/{id}
        /// Use: update selected ships status 
        /// </summary>
        /// <returns>JSON</returns>
        [HttpPut("update/status/{id}")]
        public IActionResult UpdateShipStatus(int id, [FromBody] statusBase ship)
        {
            try
            {
                var _ship = _shipService.UpdateShipStatus(id, ship);
                return Ok(new
                {
                    data = _ship,
                    message = "Successfully updated ship status."
                });
            }
            catch (AppException ex)
            {
                return BadRequest(new { error = ex.Message });
            }
        }


        /// <summary>
        /// PUT : /api/ships/deck/save
        /// Use: save information of individual deck
        /// </summary>
        /// <returns>JSON</returns>
        [HttpPut("deck/save")]
        public IActionResult UpdateDeck([FromBody] Drawing deck)
        {
            try
            {
                var _ship = _shipService.UpdateDeck(deck);
                return Ok(new
                {
                    data = _ship,
                    message = "Successfully updated ship information."
                });
            }
            catch (AppException ex)
            {
                return BadRequest(new { error = ex.Message });
            }
        }

        /// <summary>
        /// DEL : /api/ships/delete/{id}
        /// Use: delete selected ship
        /// </summary>
        /// <returns>JSON</returns>
        [HttpDelete("delete/{id}")]
        public IActionResult DeleteShip(int id)
        {
            try
            {
                _shipService.DeleteShip(id);
                return Ok(new
                {
                    message = "Selected ship has been deleted successfully."
                });
            }
            catch (AppException ex)
            {
                return BadRequest(new { error = ex.Message });
            }
        }

        /// <summary>
        /// DEL : /api/ships/deck/delete/{id}
        /// Use: delete selected deck
        /// </summary>
        /// <returns>JSON</returns>
        [HttpDelete("deck/delete/{id}")]
        public IActionResult DeleteDeck(int id)
        {
            try
            {
                _shipService.DeleteDeck(id);
                return Ok(new
                {
                    message = "Selected deck has been deleted successfully."
                });
            }
            catch (AppException ex)
            {
                return BadRequest(new { error = ex.Message });
            }
        }


    }
}