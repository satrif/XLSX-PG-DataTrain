<h1>XLSX-PG-DataTrain</h1>

## Description

This is a small web-app that provides data transfer from .xlsx file into postgresql db using [SimpleXLSX](https://github.com/shuchkin/simplexls).

System allows two types of database table data manipulation - import rows into the table or update the table by each row with defined key value.

Define session variables via some logon system or by default in /php/config.php file.

Select file, enter database and table names and the sheet number and submit.<br>The app reads table structure and shows the columns in a row.<br>
The xlsx file data with headers is shown in the table - only first and last 4 rows are visible, others are hidden (but exist on the page).

Each xlsx document header has two checkboxes:
* first allows to show < select > element with list of database table's columns to map the header;
* second is the definition of `key` column (only usable for update);

The file is loaded into /temp folder and deleted after it was read by [SimpleXLSX](https://github.com/shuchkin/simplexls) method.

To upload data into postgre database table it is required to define each desired columns after the file was read by server. 

Once upload is launched a progress bar shows the completion process.

## Constraints

Filesize is defined max 50mb, but can be changed (variable in `datatrain_permissions.php` file). 

## License

[MIT license](https://opensource.org/licenses/MIT):

- jQuery 3;
- Bootstrap 5;
