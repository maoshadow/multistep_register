# Multistep Register Drupal 8 module

This module allows creating users using a multistep form.
This form has 3 steps, the first 2 allow data to be entered, the third step creates the user and notifies the user.
The fields in steps 1 and 2 are administrable and dynamic

## Configuration of dymanic fields by step

For easy administration when the module is installed a menu link appear in the admin bar "Multistep configuration", or you can access to it using the route: /admin/config/multistep_register/settings

You can add 4 input types:
- textfield
- taxonomy Vocabulary
- number
- date

## Usage

Multistep form is in the route: /multistep/register

## License
[MIT](https://choosealicense.com/licenses/mit/)
