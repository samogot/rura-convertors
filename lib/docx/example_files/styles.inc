<?php

// Example styles
// This is an example of how a "style sheet" should be structured
// to asign PhpWord styles to HTML elements, classes, and inline
// styles.
function htmltodocx_styles_example() {
  
  // Set of default styles - 
  // to set initially whatever the element is:
  // NB - any defaults not set here will be provided by PhpWord.
  $styles['default'] = 
    array (
      'size' => 11,
    );
  
  // Element styles:
  // The keys of the elements array are valid HTML tags;
  // The arrays associated with each of these tags is a set
  // of PhpWord style definitions.
  $styles['elements'] = 
    array (
      'h1' => array (
        'bold' => TRUE,
        'align' => 'center',
        'size' => 20,
        ),
      'h2' => array (
        'bold' => TRUE,
        'align' => 'center',
        'size' => 15,
        'spaceAfter' => 150,
        'spaceBefore' => 150,
        'pageBreakBefore' => TRUE,
        ),
      'h3' => array (
        'align' => 'center',
        'size' => 12,
        'bold' => TRUE,
        'spaceAfter' => 100,
        'spaceBefore' => 100,
        ),
      'center' => array (
        'align' => 'center',
        ),
      'li' => array (
        ),
      'ol' => array (
        'spaceBefore' => 200,
        ),
      'ul' => array (
        'spaceAfter' => 150,
        ),
      'b' => array (
        'bold' => TRUE,
        ),
      'em' => array (
        'italic' => TRUE,
        ),
      'i' => array (
        'italic' => TRUE,
        ),
      'strong' => array (
        'bold' => TRUE,
        ),
      'b' => array (
        'bold' => TRUE,
        ),
      'sup' => array (
        'superScript' => TRUE,
        //'size' => 6,
        ), // Superscript not working in PhpWord 
      'u' => array (
        'underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE,
        ),
      'a' => array (
        'color' => '0000FF',
        'underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE,
        ),
      'table' => array (
        // Note that applying a table style in PhpWord applies the relevant style to
        // ALL the cells in the table. So, for example, the borderSize applied here
        // applies to all the cells, and not just to the outer edges of the table:
        'borderColor' => '000000',  
        'borderSize' => 10,
        ),
      'th' => array (
        'borderColor' => '000000',
        'borderSize' => 10,
        ),
      'td' => array (
        'borderColor' => '000000',
        'borderSize' => 10,
        ),
	  'img' => array (
	    'align' => 'center'
		)
      );
      
  // Classes:
  // The keys of the classes array are valid CSS classes;
  // The array associated with each of these classes is a set
  // of PhpWord style definitions.
  // Classes will be applied in the order that they appear here if
  // more than one class appears on an element.
  $styles['classes'] = 
    array (
      'underline' => array (
        'underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE,
        ),
       'purple' => array (
        'color' => '901391',
       ),
       'green' => array (
        'color' => '00A500',
       ),
      );
  
  // Inline style definitions, of the form:
  // array(css attribute-value - separated by a colon and a single space => array of
  // PhpWord attribute value pairs.    
  $styles['inline'] = 
    array(
      'text-decoration: underline' => array (
        'underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE,
      ),
      'vertical-align: left' => array (
        'align' => 'left',
      ),
      'vertical-align: middle' => array (
        'align' => 'center',
      ),
      'vertical-align: right' => array (
        'align' => 'right',
      ),
    );
    
  return $styles;
}