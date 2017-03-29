# TwitterDelete
A quick 'n' dirty collection of PHP scripts I cobbled together to mass-delete Twitter posts

I use the API from http://github.com/j7mbo/twitter-api-php which is under an MIT license.

Due to API limits, you're best to download your archive and save your tweet IDs to a csv file, using the CSV version of the script.  You can then use the hourly script on a cron job to delete tweets over a given age.

Edit your settings.php to put in your OAUTH and keys.  These days, this requires giving Twitter your mobile number.  Sorry 'bout that.
