<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Laravel</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

  <!-- Styles -->
  <style>
    html,
    body {
      background-color: #fff;
      color: #636b6f;
      font-family: 'Nunito', sans-serif;
      font-weight: 200;
      height: 100%;
      width: 100%;
      margin: 0;
    }

    body {
      display: flex;
    }

    .container {
      margin: auto;
      border-radius: 5px;
      background-color: #f2f2f2;
      padding: 20px;
      width: 30%;
    }

    .col-25 {
      float: left;
      width: 45%;
      margin-top: 6px;
    }

    .col-75 {
      float: left;
      width: 55%;
      margin-top: 6px;
    }

    /* Clear floats after the columns */
    .row:after {
      content: "";
      display: table;
      clear: both;
    }

    .row-btn {
      text-align: center;
    }

    input[type=email],
    input[type=password] {
      width: 80%;
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 4px;
      resize: vertical;
    }

    label {
      padding: 12px 12px 12px 0;
      display: inline-block;
    }

    input[type=submit] {
      background-color: #2d3748;
      color: white;
      padding: 12px 20px;
      border: none;
      border-radius: 4px;
      margin-top: 20px;
      width: 50%;
    }

    input[type=submit]:hover {
      background-color: #414f65;
    }

    /*
      ##Device = Laptops, Desktops
      ##Screen = B/w 1025px to 1280px
    */
    @media (min-width: 1025px) and (max-width: 1280px) {

      .col-25,
      .col-75 {
        width: 100%;
        margin-top: 0;
      }

      .container {
        width:30%
      }

      input[type=submit] {
        text-align: center;
        margin-top: 20px;
        width: 75%;
      }

    }

    /*
      ##Device = Tablets, Ipads (portrait)
      ##Screen = B/w 768px to 1024px
    */
    @media (min-width: 768px) and (max-width: 1024px) {

      .col-25,
      .col-75 {
        width: 100%;
        margin-top: 0;
      }

      .container {
        width: 75%
      }

      input[type=submit] {
        text-align: center;
        margin-top: 20px;
        width: 75%;
      }
    }

    /*
      ##Device = Tablets, Ipads (landscape)
      ##Screen = B/w 768px to 1024px
    */

    @media (min-width: 768px) and (max-width: 1024px) and (orientation: landscape) {

      .col-25,
      .col-75 {
        width: 100%;
        margin-top: 0;
      }

      .container {
        width: 75%
      }

      input[type=submit] {
        text-align: center;
        margin-top: 20px;
        width: 75%;
      }

    }

    /*
      ##Device = Low Resolution Tablets, Mobiles (Landscape)
      ##Screen = B/w 481px to 767px
    */

    @media (min-width: 481px) and (max-width: 767px) {

      .col-25,
      .col-75 {
        width: 100%;
        margin-top: 0;
      }

      .container {
        width: 75%
      }

      input[type=submit] {
        text-align: center;
        margin-top: 20px;
        width: 75%;
      }

    }

    /*
      ##Device = Most of the Smartphones Mobiles (Portrait)
      ##Screen = B/w 320px to 479px
    */
    @media (min-width: 320px) and (max-width: 480px) {

      .col-25,
      .col-75 {
        width: 100%;
        margin-top: 0;
      }

      .container {
        width: 75%
      }

      input[type=submit] {
        text-align: center;
        margin-top: 20px;
        width: 75%;
      }
    }

    .title {
      font-size: 30px;
    }

    .container-no-link {
      margin: auto;
    }
  </style>
</head>

<body>
  @if ($token)
  <div class="container">
  @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
  @endif
    <form method="POST" action="/reset_password/validate/{{$token}}" class="form-example">
      @csrf
      <div class="row">
        <div class="col-25">
          <label for="password">Nouveau mot de passe *</label>
        </div>
        <div class="col-75">
          <input type="password" id="password" name="password">
        </div>
      </div>
      <div class="row">
        <div class="col-25">
          <label for="password_confirmation">Confirmation du mot de passe *</label>
        </div>
        <div class="col-75">
          <input type="password" id="password_confirmation" name="password_confirmation">
        </div>
      </div>
      <div class="row row-btn">
        <input type="submit" value="Valider">
      </div>
    </form>
  </div>
  @else
    <div class="container-no-link">
      <div class="title">
        Ce lien n'est plus valide.
      </div>
    </div>
  @endif
</body>

</html>