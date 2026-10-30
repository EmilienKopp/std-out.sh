<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme ?? 'stdout' }}" @class(['dark' => $themeIsDark ?? false])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $metaTitle = ($metaTitle ?? null) ?: config('app.name', 'std-out').' - Where developers get on stage';
            $metaDescription = ($metaDescription ?? null) ?: 'std-out helps developers get on stage: shape your talk, rehearse timed runs, trade reviews with the speakers you follow, and go live to a room that talks back.';
            $metaImage = ($metaImage ?? null) ?: url('/og-image.png');
        @endphp

        <meta name="description" content="{{ $metaDescription }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'std-out') }}">
        <meta property="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ $metaImage }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $metaTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
        <meta name="twitter:image" content="{{ $metaImage }}">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        <x-inertia::head>
            <title>{{ config('app.name', 'std-out') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
