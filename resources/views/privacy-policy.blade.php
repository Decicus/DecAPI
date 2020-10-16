<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootswatch/4.5.2/darkly/bootstrap.min.css" integrity="sha384-nNK9n28pDUDDgIiIqZ/MiyO3F4/9vsMtReZK39klb/MtkZI3/LtjSjlmyVPS3KdN" crossorigin="anonymous">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.14.0/css/all.css" integrity="sha384-HzLeBuhoNPvSl5KYnjx0BT+WB0QEEqLprO+NBkkk5gbc67FTaL7XIGa2w1L0Xbgc" crossorigin="anonymous">
        <style type="text/css">
            .card {
                margin-top: 2em;
            }
        </style>
        <title>{{ env('SITE_TITLE', 'DecAPI') }} | {{ $page ?? '[Undefined]' }}</title>
    </head>
    <body>
        <div class="container">
            <h1>Privacy Policy for DecAPI</h1>
            <p><strong>Last updated: October 16th, 2020.</strong></p>

            <p>This privacy policy describes the information that the DecAPI service collects from you when using the service.</p>

            <div class="card" id="automatic-collection">
                <div class="card-header">
                    Information that is automatically collected
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Certain information is automatically collected when you visit our website or use our service:
                        <ul>
                            <li>Your IP address</li>
                            <li>Your browser's user agent</li>
                            <li>What page you were visiting</li>
                            <li>Date &amp; time of visit</li>
                        </ul>

                        Information is only stored for up to 14 days on the DecAPI web servers (using the NGINX access log feature) before being automatically rotated &amp; deleted.
                        <br>
                        The information is only stored in raw log files and is not processed or shared with any other parties.
                    </p>
                </div>
            </div>

            <div class="card" id="twitch">
                <div class="card-header">
                    Twitch
                </div>
                <div class="card-body">
                    <p class="card-text">
                        When accessing Twitch-related information, we may store and cache public user information provided for up to 30 days.
                        <br>
                        Public user information includes:
                        <ul>
                            <li>Unique Twitch ID</li>
                            <li>Twitch username</li>
                        </ul>
                        Cached user information is only used to prevent excessive requests to the Twitch API.
                    </p>
                </div>
            </div>

            <div class="card" id="twitch-authenticated">
                <div class="card-header">
                    Authenticated information from Twitch
                </div>
                <div class="card-body">
                    <p class="card-text">
                        For Twitch-related information that requires authentication (such as subscription-related information), DecAPI will store the following information indefinitely:
                        <ul>
                            <li>Unique Twitch ID (public)</li>
                            <li>Twitch username (public)</li>
                            <li>Access token ("OAuth token") used for accessing restricted user information via Twitch's API (private)</li>
                        </ul>
                        If you no longer want DecAPI to store this information, do the following:
                        <ul>
                            <li>Go to your <a href="https://www.twitch.tv/settings/connections">Twitch settings, the "Connections" page</a></li>
                            <li>Scroll down to the "Other connections" section</li>
                            <li>Find "DecAPI" and click "Disconnect"</li>
                        </ul>
                        Once you have disconnected DecAPI from your Twitch account, your information will automatically be deleted in less than 24 hours.
                    </p>
                </div>
            </div>

            <div class="card" id="youtube-and-google">
                <div class="card-header">
                    YouTube and Google
                </div>
                <div class="card-body">
                    <p class="card-text">
                        DecAPI uses YouTube API Services for accessing YouTube-related data.
                        <br>
                        By using DecAPI for YouTube-related data, you accept <a href="https://www.youtube.com/t/terms">YouTube's Terms of Service</a> and <a href="https://policies.google.com/privacy">Google's privacy policy</a>.
                        <br>
                        No YouTube-related user information is stored or cached in the DecAPI service.
                        <br>
                        No YouTube-related user information is not being shared with any other parties via the DecAPI service.
                        <br>
                        Information from the YouTube API is processed by extracting the relevant information (such as video title or video ID) and then returning it with the response.
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>
