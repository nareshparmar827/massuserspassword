# === MASS Users Password Reset Pro ===



MASS Users Password Reset lets you easily reset the password of all users. 

== Description ==

MASS Users Password Reset is a WordPress Plugin that lets you resets the password of all users. It can group the users according to their role as well as by user selected custom fields and resets password of that group. It sends notification email to users about their new randomly generated password. 


Features
•   Easy installation
•   Role wise as well as custom field bifurcation of users 
•	Individual user reset password
• 	Bulk action of Reset Password
•	Sends Notifications to selected role users
• 	Customize Email Template
• 	Translatable 



== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Install the plugin via WordPress or download and upload the plugin to the /wp-content/plugins/
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. You can see the 'Mass Users Password Reset' submenu inside the 'Users' menu.
4. If you have free version of this plugin then delete that folder.


== Frequently Asked Questions ==

= What is the length of generated password? =

The length of randomly generated password is 8 characters, but by applying filter 'mupr_password_length' you can customize it. For Example: Write this code in function file
add_filter('mupr_password_length','my_theme_function');
function my_theme_function(){
	return 6;
}

= When notification mail will be send? =

When user will choose to generate new password, an email with the new random password will be sent to users. 

== Screenshots ==

1. It shows the list of users and options.
2. It shows Reset password Email format
