<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookstore</title>
    <!-- Include CSS, Bootstrap, etc. -->
</head>
<body>
    @extends('header') <!-- Include the header file -->

    <!-- Main Content -->
    <main>
        @yield('content') <!-- Content from individual pages will be injected here -->
    </main>

    @extends('footer') <!-- Include the footer file -->
</body>
</html>
