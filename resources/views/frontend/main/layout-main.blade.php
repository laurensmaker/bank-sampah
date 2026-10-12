<!DOCTYPE html>
<html lang="en">
    
<meta http-equiv="content-type" content="text/html;charset=UTF-8" /><!-- /Added by HTTrack -->
<head>
        <meta charset="utf-8">
        <meta name="description" content="Angels Cakes - Website"/>
        <meta name="keywords" content="Cakes - Wedding Cakes - Angels Cakes -"/>
        <meta name="author" content="Angels-Cakes">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- title -->
        <title>BANK SAMPAH JAYAPURA</title>
        <!-- favicon -->
        <link rel="icon" type="image/x-icon" href="{{ asset('frontend/img/logo/favicon.png') }}">
        <!-- bootstrap css -->
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/bootstrap.min.css') }}">
        <!-- magnific-popup css -->
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/magnific-popup.css') }}">
        <!-- animate css -->
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/animate.min.css') }}">
        <!-- bootstrap icon -->
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/bootstrap-icons.css') }}">
        <!-- font-awesome css -->
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/all.min.css') }}">
        <!--fether css -->
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/feather.css') }}">
        <!-- owl css -->
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/owl.carousel.min.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/owl.theme.default.min.css') }}">
        <!-- swiper-bundle css -->
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/swiper-bundle.min.css') }}">
        <!-- slick slider css -->
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/slick.css') }}">
        <!-- style css -->
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/style.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/other-page.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/account.css') }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>
    <body>
        
        
        <!-- header start -->
        @include('frontend.main.header')
        <!-- header end -->
        <main>
          @yield('content')
        </main>
       
        {{-- footer --}}
        @include('frontend.main.footer')
        
        <!-- Google Maps API -->
        <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA8KTs5HHyDda2RGA64Gg-G04PmjpHm05Y&callback=initMap"></script>
    </body>

</html>