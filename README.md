# [PHP Online Exam Project](http://b0221.com/online-exam/)

PHP web-app that lets users register as examiner or examinee. Examiners can create exams, and Examinees take them. The app contains various user authentication features.

## How it works

`index.php` requires `loader.php` then inits the `Router` library class. The Router class then parses the url to 'route' to different controller classes in the app. The specified controller class in the url is then initialized, then calls the specified method in the url, If there are any specified parameters in the url, the method if it permits, takes it in as an argument. The method then renders a view if it has one.

## Project Structure
* `/config/config.php` contains defined constants
* `/lib/` contain all parent classes and helper methods
* `/src/controllers/` contain all controller classes
* `/src/models/` contain all model classes
* `/views/` contain all view templates the controller classes render 

## todo 
* refactor code