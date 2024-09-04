<?php
    $ruta = Rutas::RutaProyecto();
?>

<html lang="">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MARIUS SPORT</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="vistas/assets/plugins/fontawesome-free/css/all.min.css">

    <!-- Theme style -->
    <link rel="stylesheet" href="vistas/assets/dist/css/adminlte.min.css">

    <style>
    .video-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: -1;
    }

    .login-page {
        position: relative;
        width: 100%;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background: rgba(0, 0, 0, 0.5);
        /* Optional: Add a semi-transparent background */
    }

    .login-box {
        z-index: 1;
    }
    </style>
</head>
<!-- <body class="hold-transition login-page" style="background-image: url('vistas/assets/imagenes/fondo_login_2.jpg');  background-repeat: no-repeat;  background-position: 0% 50%; background-size: 100% 110%;"> -->

<body class="login-page">

    <!-- Video de fondo -->
    <video autoplay muted loop class="video-background">
        <source src="vistas/assets/plugins/video/mp4/sslogo.mp4" type="video/mp4">
        Your browser does not support HTML5 video.
    </video>

    <div class="login-box">

        <div class="card card-outline card-primary">

            <div class="card-header text-center">

                <h2 class="h2"><b>MARIUS SPORT</b></h2>

            </div><!-- /.card-header -->

            <div class="card-body">

                <form method="post" class="needs-validation-login" autocomplete="off" novalidate>

                    <!-- USUARIO DEL SISTEMA -->
                    <div class="input-group mb-3">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                        <input type="text" class="form-control" placeholder="Usuario del sistema" id="loginUsuario"
                            autocomplete="off" required>
                        <div class="invalid-feedback">Debe ingresar su usuario!</div>
                    </div><!-- /.input-group USUARIO -->

                    <!-- PASSWORD DEL USUARIO DEL SISTEMA -->
                    <div class="input-group mb-3">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                        <input type="password" class="form-control" placeholder="ingrese su password" id="loginPassword"
                            autocomplete="off" required>
                        <div class="invalid-feedback">Debe ingresar su contraseña!</div>
                    </div><!-- /.input-group PASSWORD -->

                    <div class="row">
                        <div class="col-md-12 text-center">
                            <a class="btn btn-info w-100 fw-bold" id="btnIniciarSesion">INICIAR SESION</a>
                        </div>

                    </div>

                </form>

            </div><!-- /.card-body -->

        </div>

    </div>
    <!-- /.login-box -->


    <!-- jQuery -->
    <script src="vistas/assets/plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="vistas/assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="vistas/assets/dist/js/adminlte.min.js"></script>

    <script>
    $(document).ready(function() {

        $("#btnIniciarSesion").on('click', function() {
            // alert("entro")
            fnc_login();
        })

        $('#loginPassword').keypress(function(e) {
            var key = e.which;
            if (key == 13) // the enter key code
            {
                fnc_login();
            }
        });

        $("#btnReestablecerPassword").on('click', function() {
            $("#modalReestablecerPassword").modal('show');
        })

        $("#confirmar_password").change(function() {
            if ($("#confirmar_password").val() != $("#password").val()) {

                $("#confirmar_password").parent().addClass("was-validated")
                $("#confirmar_password").parent().children(".invalid-feedback").html(
                    "Las contraseñas no coinciden");
                $("#confirmar_password").val(
                    "") //limpiar el valor para que se muestre el mensaje de validación
                return;
            }
        })

        $("#password").change(function() {

            if ($("#password").val().length < 6) {
                $("#password").parent().addClass("was-validated")
                $("#password").parent().children(".invalid-feedback").html("Mínimo 6 caracteres");
                $("#password").val("") //limpiar el valor para que se muestre el mensaje de validación
                return;
            }
        })

        $("#btnCambiarPassword").on('click', function() {
            fnc_CambiarPassword();
        });

    })

    function fnc_login() {


        var forms = document.getElementsByClassName('needs-validation-login');

        // Loop over them and prevent submission
        var validation = Array.prototype.filter.call(forms, function(form) {

            if (form.checkValidity() === true) {

                var formData = new FormData();
                formData.append('accion', 'login');
                formData.append('usuario', $("#loginUsuario").val());
                formData.append('password', $("#loginPassword").val());


                response = SolicitudAjax("ajax/auth.ajax.php", "POST", formData);

                if (response["tipo_msj"] == "success") {
                    $("#btnIniciarSesion").addClass('disabled');

                    mensajeToast(response["tipo_msj"], response["msj"]);

                    setInterval(() => {
                        $("#btnIniciarSesion").removeClass('disabled');
                        window.location = "<?php echo $ruta; ?>";
                    }, 1200);


                } else {
                    mensajeToast(response["tipo_msj"], response["msj"]);
                    $("#btnIniciarSesion").removeClass('disabled');
                }

            } else {
                mensajeToast('error', 'Ingrese el usuario y contraseña');

            }

        })



    }

    function fnc_CambiarPassword() {

        form_usuario_validate = validarFormulario('needs-validation-usuario');

        //INICIO DE LAS VALIDACIONES
        if (!form_usuario_validate) {
            mensajeToast("error", "complete los datos obligatorios");
            return;
        }

        Swal.fire({
            title: 'Está seguro(a) de cambiar la contraseña?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Si!',
            cancelButtonText: 'No',
        }).then((result) => {

            if (result.isConfirmed) {

                var formData = new FormData();

                formData.append('accion', 'cambiar_password');
                formData.append('usuario', $("#usuario").val());
                formData.append('password', $("#password").val());

                response = SolicitudAjax('ajax/usuarios.ajax.php', 'POST', formData);

                Swal.fire({
                    position: 'top-center',
                    icon: response['tipo_msj'],
                    title: response['msj'],
                    showConfirmButton: true,
                    timer: 2000
                });

                $("#modalReestablecerPassword").modal('hide');

            }

        })
    }
    </script>
</body>

</html>