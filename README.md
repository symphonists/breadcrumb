# Breadcrumb

- Version: 1.1
- Date: July 3rd, 2011
- Author: Alistair Kearney (alistair@symphony21.com); Solutions Nitriques (open-source (at) nitriques.com)
- Requirements: Symphony 2.2+

Includes a Data Source that, based on the current page, will provide a focused XML structure representing the parent hierarchy. It is more efficient than a conventional Navigation DS, since there is less redundant data and is much easier to traverse with XPath.

## Installation

** Note: The latest version can always be grabbed with "git clone git://github.com/symphonists/breadcrumb.git" **

1. Upload the 'breadcrumb' folder in this archive to your Symphony 'extensions' folder.

2. Enable it by selecting the "Breadcrumb", choose "Enable/Install" from the with-selected menu, then click Apply.

3. Add the "Breadcrumbs" Data Source to your page

## Example XML

	<breadcrumb>
		<page path="about">About</page>
		<page path="about/contact">Contact</page>
		<page path="about/contact/office">Our Office</page>
	</breadcrumb>
	
## Changelog

1.1 

- Compatible with the Multilingual Field and Language Redirect extensions
