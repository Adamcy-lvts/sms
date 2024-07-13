<!DOCTYPE html>
<html lang="en">

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<link href="https://cdn.tailwindcss.com/2.2.19/tailwind.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<title>Document</title>
<style>
    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 3rem;
        color: #000;

    }

    h1,
    h2 {
        text-align: center;
        margin: 0;
        padding: 10px 0;
    }

    h1 {
        font-size: 15px;
        font-weight: 600;
    }

    h2 {
        font-size: 10px;
        font-weight: 600;
    }

    p {
        margin: 10px 0;
        padding: 0;
        line-height: 1.3;
        font-size: 13px;
    }

    div {
        /* margin: 10px 0; */
        font-size: 13px
    }


    hr {
        border: 0.5px solid #000;
        margin: 10px 0;
    }

    figure {
        display: flex;
        justify-content: center;
        margin: 10px 0;
    }

    .container {
        width: 100%;
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .logo {
            display: block;
            margin: 0 auto;
        }
</style>



<body>


    <div>
        <img src="{{ $logoUrl }}" width="40" alt="">
    </div>
    {{-- {{ $logoUrl }} --}}
    @if (!empty($content))
        <div class="text-sm p-8">
            {!! $content !!}
        </div>
    @else
        <p>No content available.</p>
    @endif



<script>
    document.addEventListener("DOMContentLoaded", function() {
        const figure = document.querySelector('figure.attachment.attachment--preview');
        if (figure) {
            const img = document.createElement('img');
            img.className = 'logo';
            img.src = '{{ $logoUrl }}';
            img.width = 103;
            img.height = 130;
            figure.parentNode.replaceChild(img, figure);
        }
    });
</script>
</body>

</html>
