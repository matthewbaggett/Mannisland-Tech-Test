# Mannisland-Tech-Test

PHP Developer Exercise

Task Description:
Using the SOAP web service provided as your data source, create a basic web app within an PHP MVC framework of your choice (including a basic HTML frontend) that allows a user to do the following:
Select from a list of available companies
Using the associated company’s symbol, obtain the company’s current stock price & directors names
Save the company name & current stock quote to a MySQL database, showing a history of previous 5 results to the user. 
Note: One of the methods on the web service throws an intentional SoapFault, handle this exception within your app and notify the user with an appropriate error message. 

Data Source:
Web Service Location: 
http://mannisland.co.uk/exercise/Service.php?wsdl

Web Service Authentication Details:
username: demo@mannisland.co.uk
password: m4nn1sland

Notes:
We have setup a basic SOAP web service located above. The web service has three methods (getCompanies, getCompanyDirectorsBySymbol & getQuote). These methods will allow you to get the data required to develop your app.  Look up SoapClient (http://www.php.net/manual/en/book.soap.php)  if you are unfamiliar with web services in PHP.

Tips: soapUI will allow you to test the web service and its various methods from your desktop. 


