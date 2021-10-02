using System.Linq;
using Microsoft.AspNetCore.Hosting;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.Extensions.Configuration;
using Privacy.Util;
using Privacy.Entities;
using Privacy.WebApp.Models;
using Privacy.Identidade;
using Microsoft.AspNetCore.Http;
using Microsoft.Extensions.Options;
using Microsoft.AspNetCore.Authentication;
using Microsoft.AspNetCore.Authentication.Cookies;
using Microsoft.AspNetCore.Authorization;
using System.Threading.Tasks;
using System.Security.Claims;
using System.Collections.Generic;
using System;
using System.Net;
using Privacy.Models;

using Microsoft.AspNetCore.Identity;

namespace Privacy.Pages
{
    [AllowAnonymous]
    public class LoginModel : PageModel
    {
        #region Propriedades
        [BindProperty]
        public string Usuario { get; set; }

        [BindProperty]
        public string Senha { get; set; }

        [BindProperty]
        public string Mensagem { get; set; }

        [BindProperty]
        public string NovoUsuario { get; set; }

        private IHostingEnvironment _environment;
        private readonly AppSettingsJWT settingsJWT;
        private readonly HttpContext httpContext;
        private IConfiguration _configuration;
        #endregion


        [Obsolete]
        public LoginModel(IConfiguration configuration, IHostingEnvironment environment, IOptions<AppSettingsJWT> settingsJWT)
        {
            _configuration = configuration;
            _environment = environment;
            this.settingsJWT = settingsJWT.Value;
            this.httpContext = httpContext;
        }

        public IActionResult OnGet()
        {
            // HttpContext.Session.SetObjectAsJson("USUARIO", null);

            if (this.User.Identity.IsAuthenticated)
            {
                HttpContext.SignOutAsync(CookieAuthenticationDefaults.AuthenticationScheme);
                return RedirectToPage("/Index");
            }

            return Page();
        }

        public IActionResult OnPost()
        {
            if (string.IsNullOrEmpty(NovoUsuario))
            {
                if ((Usuario.IsNullOrEmpty() || Senha.IsNullOrEmpty()))
                    Mensagem = "Login/Senha obrigatórios em branco!";

                else
                {
                    using (PrivacyContext context = new PrivacyContext())
                    {
                        var usuario = context.Usuario.Where(x => (Usuario.Contains("@") ? x.Login.ToLower() == Usuario.ToLower() : x.NomeUsuario.ToLower() == Usuario.ToLower()) && x.Senha == Criptography.Encrypt(Senha)).FirstOrDefault();

                        if (usuario != null)
                        {
                            //HttpContext.Session.SetObjectAsJson("USUARIO", usuario);
                            var token = Token.GerarToken(usuario.IdUsuario.ToString(), usuario.Nome, usuario.Email, usuario.DataNascimento.Value, GerarClaims(usuario), this.settingsJWT);
                            new CookieAuthentication().Login(HttpContext, token).Wait();
                            if (!usuario.Ativo)
                                return RedirectToPage("/Welcome");
                            //Mensagem = "Usuário inativo! Verifique a caixa de entrada de seu email!";
                            else
                            {
                                return RedirectToPage("/Index", new { NomeUsuario = usuario.Nome });
                            }
                        }
                        else
                            Mensagem = (Usuario.Contains("@") ? "E-mail/Senha inválidos" : "Usuário/Senha inválidos");
                    }
                }
            }
            else
            {
                if (Usuario.Contains("@"))
                {
                    using (PrivacyContext context = new PrivacyContext())
                    {

                        Usuario NovoUsuario = new Usuario();

                        NovoUsuario = UsuarioModel.CadastrarNovoUsuario(Usuario, Senha, this.NovoUsuario);

                        if (NovoUsuario != null)
                        {
                            var token = Token.GerarToken(NovoUsuario.IdUsuario.ToString(), NovoUsuario.Nome, NovoUsuario.Email, NovoUsuario.DataCadastro, GerarClaims(NovoUsuario), this.settingsJWT);
                            new CookieAuthentication().Login(HttpContext, token).Wait();
                            return RedirectToPage("/MyProfile", new { Id = WebUtility.HtmlEncode(Criptography.Encrypt(NovoUsuario.IdUsuario.ToString())) });
                            //return RedirectToRoute("/MyProfile", new { Id = WebUtility.HtmlEncode(Criptography.Encrypt(ultimoUsuario.IdUsuario.ToString())) });
                        }

                        else
                            Mensagem = "Ocorreu uma falha ao realizar seu cadastro. Tente novamente mais tarde.";
                    }
                }
                else
                {
                    Mensagem = "Informe um e-mail válido.";
                    return Page();
                }

                
            }

            
            

            return null;
        }

        private List<Claim> GerarClaims(Usuario usuario)
        {
            List<Claim> list = new List<Claim>();

            list.Add(new Claim("IdUsuario", ConvertString(usuario.IdUsuario)));
            list.Add(new Claim("IdEtnia", ConvertString(usuario.IdEtnia)));
            list.Add(new Claim("IdGenero", ConvertString(usuario.IdGenero)));
            list.Add(new Claim("IdInteresse", ConvertString(usuario.IdInteresse)));
            list.Add(new Claim("Nome", ConvertString(usuario.Nome)));
            list.Add(new Claim("Login", ConvertString(usuario.Login)));
            list.Add(new Claim("CPF", ConvertString(usuario.CPF)));
            list.Add(new Claim("DataNascimento", ConvertString(usuario.DataNascimento)));
            list.Add(new Claim("Email", ConvertString(usuario.Email)));
            list.Add(new Claim("Celular", ConvertString(usuario.Celular)));
            list.Add(new Claim("FotoPerfil", ConvertString(usuario.FotoPerfil)));
            list.Add(new Claim("FotoCapa", ConvertString(usuario.FotoCapa)));
            list.Add(new Claim("PerfilPublico", ConvertString(usuario.PerfilPublico)));
            list.Add(new Claim("Cidade", ConvertString(usuario.Cidade)));
            list.Add(new Claim("Estado", ConvertString(usuario.Estado)));
            list.Add(new Claim("Pais", ConvertString(usuario.Pais)));
            list.Add(new Claim("QuantoQuer", ConvertString(usuario.QuantoQuer)));
            list.Add(new Claim("SobreMim", ConvertString(usuario.SobreMim)));
            list.Add(new Claim("DataCadastro", ConvertString(usuario.DataCadastro)));
            list.Add(new Claim("Ativo", ConvertString(usuario.Ativo)));

            return list;

        }
        private string ConvertString<T>(T valor)
        {
            if (valor == null)
                return "";
            else
                return valor.ToString();
        }


    }

}
