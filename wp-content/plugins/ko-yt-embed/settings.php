<?php
/**
 * Settings page for KO YT Embed
 */
 
 // The only setting is the api key

?>

<form method="post" action="options.php">
    <?php settings_fields('ko-yt-embed-settings-group'); ?>
    <?php do_settings_sections('ko-yt-embed-settings-group'); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">API Key</th>
        <td><input type="text" name="ko_yt_api_key" value="<?php echo get_option('ko_yt_api_key'); ?>" /></td>
        </tr>
    </table>
    
    <?php submit_button(); ?>
</form>