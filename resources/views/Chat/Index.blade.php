@extends('layouts.app')

@section('head')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Application</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Style for round user image */
        .user_image {
            width: 40px;
            height: 40px;
            background-size: cover;
            background-position: center;
            border-radius: 50%;
        }

        /* Style for sender messages */
        .send_messages {
            /* display: flex;
                                                                                                                                                                                                                                justify-content: flex-end;
                                                                                                                                                                                                                                margin-bottom: 15px; */
            display: flex;
            justify-content: flex-start;
            margin-bottom: 15px;
            flex-direction: row-reverse;
        }

        .send_messages .msg-bubble {
            background-color: #d3d3d3;
            /* Gray background color for sender messages */
            border-radius: 10px;
            max-width: 70%;
            padding: 10px;
            display: flex;
            align-items: center;
        }

        /* Move the user image to the right */
        .send_messages .user_image {
            margin-left: 10px;
        }

        /* Style for receiver messages */
        .received_messages {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 15px;
        }

        .received_messages .msg-bubble {
            background-color: #3498db;
            /* Blue background color for receiver messages */
            border-radius: 10px;
            max-width: 70%;
            padding: 10px;
            display: flex;
            align-items: center;
        }

        /* Move the user image to the left */
        .received_messages .user_image {
            margin-right: 10px;
        }

        /* Style for card-body to make it scrollable and take 100% height */
        .card-body {
            overflow-y: auto;
            max-height: calc(100vh - 150px);
            /* Adjusted based on your card-footer height */
        }


        .users-list {
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .user-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            cursor: pointer;
        }

        .user-item:hover {
            background-color: #e6e6e6;
        }

        .user-item p {
            margin-left: 10px;
            font-weight: bold;
        }

        /* Style for round user image in users list */
        .user-item .user_image {
            width: 30px;
            height: 30px;
            background-size: cover;
            background-position: center;
            border-radius: 50%;
            margin-right: 10px;
        }

        .user-item.active {
            background-color: #007bff;
            /* Adjust the color as needed */
            color: #fff;
            /* Adjust the text color as needed */
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <br>
        <div class="row">
            <div class="col-md-2">
                <div class="users-list">
                    @foreach ($users as $user)
                        <div class="user-item" id="{{ $user->id }}" onclick="selectUser({{ $user->id }})">
                            <a href="#">
                                <div class="user_image" style="background-image: url('{{ URL::asset('assets/img/avatars/1.png') }}')"></div>
                            </a>
                            <p>{{ $user->name }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <p>Messenger</p>
                    </div>
                    <div class="card-body">

                    </div>
                    <div class="card-footer">
                        <input type="text" class="form-control" id="messageInput" placeholder="Enter your message...">
                        <button type="button" onclick="sendMessage()" class="btn btn-success" id="sendButton">Send</button>


                        <input type="file" id="imageUpload" accept="image/*" style="display:none;">
                        <button type="button" onclick="document.getElementById('imageUpload').click();" class="btn btn-info">Upload Image</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('script')
    <script>
        var userID = null;
        // Trigger send action on Enter key press
        $("#messageInput").on('keypress', function(e) {
            if (e.which === 13) {
                sendMessage(); // Call your send message function
            }
        });

        function sendMessage() {
            $.ajax({
                type: 'post',
                url: '{{ URL('send-message') }}',
                data: {
                    '_token': "{{ csrf_token() }}",
                    'message': $("#messageInput").val(),
                    'user': userID,
                },
                success: function(data) {
                    console.log(data);

                    $(".card-body").append(`
            <div class="send_messages">
                <div class="user_image" style="background-image: url({{ URL::asset('assets/img/avatars/1.png') }})"></div>

                <div class="msg-bubble">
                    <div class="msg-text">
                        ${data.message}
                    </div>
                </div>
            </div>
        `);
                }
            });
            $("#messageInput").val('');

        };

        newMessages = new EventSource(`{{ URL('/get-new-messages') }}/0}`);

        function setupEventSource() {
            newMessages.close();
            if (userID) {
                // Close existing EventSource connection if it exists
                if (newMessages) {
                    newMessages.close();
                }

                // Create a new EventSource for the selected user
                newMessages = new EventSource(`{{ URL('/get-new-messages') }}/${userID}`);

                newMessages.onmessage = function(event) {
                    let message = JSON.parse(event.data);

                    $(".card-body").append(`
                    <div class="received_messages">
                        <div class="user_image" style="background-image: url({{ URL::asset('assets/img/avatars/1.png') }})"></div>
                        <div class="msg-bubble">
                            <div class="msg-text">
                                ${message.message}
                            </div>
                        </div>
                    </div>
                `);
                };
            }
        }


        function selectUser(userId) {
            $(".user-item").removeClass("active");
            $(`#${userId}`).addClass("active");
            $(".card-body").empty();
            userID = userId;
            setupEventSource();
            getChatHistory();
        }

        function getChatHistory() {
            $(".card-body").empty();

            $.ajax({
                type: 'get',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token')
                },
                url: '{{ URL('chat-history') }}',
                data: {
                    'userID': userID
                },
                success: function(data) {
                    console.log(data);

                    // Iterate over each message and append it to the card body
                    data.forEach(function(message) {
                        if (message.send_by == {{ Auth::user()->id }}) {
                            // Append sent messages
                            $(".card-body").append(`
                        <div class="send_messages">
                            <div class="user_image" style="background-image: url({{ URL::asset('assets/img/avatars/1.png') }})"></div>
                            <div class="msg-bubble">
                                <div class="msg-text">
                                    ${message.message}
                                </div>
                            </div>
                        </div>
                    `);
                        } else {
                            // Append received messages
                            $(".card-body").append(`
                        <div class="received_messages">
                            <div class="user_image" style="background-image: url({{ URL::asset('assets/img/avatars/1.png') }})"></div>
                            <div class="msg-bubble">
                                <div class="msg-text">
                                    ${message.message}
                                </div>
                            </div>
                        </div>
                    `);
                        }
                    });
                }
            });
        }











        $('#imageUpload').on('change', function() {
            var file = $(this)[0].files[0];
            var formData = new FormData();
            formData.append('image', file);
            formData.append('_token', "{{ csrf_token() }}");

            $.ajax({
                url: '{{ URL('upload-chat-photo') }}', // Replace with your server-side endpoint
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    // Handle the success response from the server
                    console.log(response);
                },
                error: function(error) {
                    // Handle the error response from the server
                    console.error(error);
                }
            });
        });
    </script>
@endsection
