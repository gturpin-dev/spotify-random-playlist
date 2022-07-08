# Requirements

You must have ACF Pro installed to use this plugin.
You must have a Spotify premium account.

# Installation

- Download all plugins files from here
- Name the folder "spotify"
- Do a `composer install` in the folder
- Import the plugin in your WP
- Go to BO in ACF fields and sync the local fields that come with this plugin
- Go to your Spotify account 
  - Generate an app and store the information in your WP profile in the ACF fields
  - Store the client ID 
  - Store the client Secret
  - Store the Redirect URI => https://post-type-handler.ddev.site:8443/wp-json/spotify/v1/auth_callback
- In the Spotify APP, create a new playlist and get the playlist ID
- Go to your WP profile
  - Set your Custom playlist ID
  - For each elem in the repeater, set the Artist name and its ID ( the name is only for you )
  - Set the number of tracks per artist
  - Update your profile
  - Now Click on the link to connect to spotify, if the page refresh and you dont see the link anymore, you are connected
- Go to "Spotify Playlist" and start processing cache tracks ( will be a CRON later )
- With the right amount of stored data you can "generate a playlist"
- Your PL is ready to use