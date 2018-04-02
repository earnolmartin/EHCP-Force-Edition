<html>

This implemented only help you a bit more.... <hr>
<b>Mysql Troubleshooter Version 0.29, last modified at 18.12.2008 by bvidinli </b><br><br><br>


if you get mysql error:<br>

<ul>if You get error: Can't connect to local MySQL server through socket<br>
Try this: <br>
Go to your console and run <br>
sudo service mysql start<br>

<hr>

</ul>
<ul>
<li>
if you just installed ehcp:
<ul>
<li>Check your config.php for right password of mysql ehcp user, like: [$dbpass='12345';] in your ehcp dir, /var/www/vhosts/ehcp</li>
<li>Check your config.php for right password of mysql root user, like: [$dbrootpass='12345';] in your ehcp dir, /var/www/vhosts/ehcp</li>
<li>if you dont know your mysql root pass, try to learn it first.. ask me/on forum howto learn if you dont know.. or look <a href='http://www.ehcp.net/?q=node/160'>here: mysql password recovery</a></li>
<li>After you learned your mysql root pass, try to re-install ehcp, because your first installation may failed because of wrong mysql root pass. </li>
</ul>
</li>

<hr>
<li>
if you got this error after you used ehcp for some time.. (it was okey before)
<ul>
<li>Check your config.php for right password of mysql ehcp user, like: [$dbpass='12345';] in your ehcp dir, /var/www/vhosts/ehcp</li>
<li>Check your config.php for right password of mysql root user, like: [$dbrootpass='12345';] in your ehcp dir, /var/www/vhosts/ehcp</li>
<li>Did you recently changed your mysql pass for ehcp user, or did your re-installed mysql ? test your mysql ehcp user pass in phpmyadmin</li>
<li>Do either: 

<ul>
<li>reset your mysql ehcp user pass to what you defined in config.php</li>
<li>write right password to your config.php </li>
that is, (mysql pass -> pass in config.php) or (pass in config.php -> mysql pass )

</ul></li>
</ul>
</li>

</ul>

<br>
<a href=../troubleshoot.php>Back to troubleshoot </a><br>
<a href='http://www.ehcp.net/?q=node/245'>http://www.ehcp.net/?q=node/245, mysql password howto</a><br>
<a href='http://www.ehcp.net/?q=forum'>ehcp Forums</a>
<a href='http://www.ehcp.net/helper/yardim/cats.php'>ehcp Helper</a>
<a href='http://www.ehcp.net'>ehcp website Homepage</a>

<a href="/">Home</a>



</html>
