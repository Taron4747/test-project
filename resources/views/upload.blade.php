<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Excel File</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>

</head>
<body class="container py-5">

    <h1 class="mb-4">Upload Excel File (.xlsx)</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('upload.handle') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label for="file" class="form-label">Choose Excel File</label>
            <input type="file" name="file" id="file" class="form-control" accept=".xlsx" required>
        </div>

        <button type="submit" class="btn btn-primary">Upload</button>
    </form>
    <h2 class="mt-5">Imported Data</h2>
    <ul id="imported-data" class="list-group mt-3"></ul>

    <script>
        // Initialize Pusher
        const pusher = new Pusher("{{ env('PUSHER_APP_KEY') }}", {
            cluster: "{{ env('PUSHER_APP_CLUSTER') }}",
            encrypted: true
        });

        const channel = pusher.subscribe("import-channel");
        channel.bind("row.imported", function(data) {
            const list = document.getElementById("imported-data");
            const item = document.createElement("li");
            item.classList.add("list-group-item");
            item.textContent = `ID: ${data.row.id}, Name: ${data.row.name}, Date: ${data.row.date}`;
            list.prepend(item);
        });

       
    </script>
</body>
</html>
