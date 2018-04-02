<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/india/footer.php begin -->
                    <tr> 
                      <td style="background-image:url(<?php echo $net2ftp_globals["image_url"]; ?>/backgrounds/bgmid.gif); vertical-align: top;">
                        <table border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                          <tbody>
                            <tr> 
                              <td>
                                <img src="<?php echo $net2ftp_globals["image_url"]; ?>/backgrounds/footbgline.gif" style="display: block; height: 8px; width: 998px; padding-left: 1px; padding-right: 1px;" alt="footer" />
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </td>
                    </tr>
                    <tr> 
                      <td style="background-image:url(<?php echo $net2ftp_globals["image_url"]; ?>/backgrounds/footbg1.gif); height: 41px; vertical-align: top; text-align: center;">
                        <table border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                          <tbody>
                            <tr> 
                              <td class="text_white" style="background-image:url(<?php echo $net2ftp_globals["image_url"]; ?>/backgrounds/footerbgline2.gif); height: 41px; text-align: center;">
                                <a href="<?php echo $net2ftp_globals["application_rootdir_url"]; ?>/help.html" class="text_white"><?php echo __("Help Guide"); ?></a> | <a href="javascript:go_to_forums();" class="text_white"><?php echo __("Forums"); ?></a>| <a href="<?php echo $net2ftp_globals["application_rootdir_url"]; ?>/LICENSE.txt" class="text_white"><?php echo __("License"); ?></a>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
            <tr> 
              <td class="txtfieldmatter" style="height: 35px; text-align: center;">
                <?php echo __("Powered by"); ?> net2ftp - a web based FTP client <br />
                Add to: <a href="http://del.icio.us/post?url=http://www.net2ftp.com">Del.icio.us</a> | <a href="http://digg.com/submit?phase=2&amp;url=http://www.net2ftp.com">Digg</a> | <a href="http://reddit.com/submit?url=http://www.net2ftp.com&amp;title=net2ftp%20-%20a%20web%20based%20FTP%20client">Reddit</a>
              </td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
  </tbody>
</table>

<script type="text/javascript">
function go_to_forums() {
  alert('<?php echo __("You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."); ?>');
  document.location = "http://www.net2ftp.org/forums";
} // end function forums
</script>

<!-- net2ftp version <?php echo $net2ftp_settings["application_version"]; ?> -->
<!-- Template /skins/india/footer.php end -->
