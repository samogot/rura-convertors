<?php

/**
 * HTMLtodocx
 * HTML to docx Converter
 * - HTML converter for use with PhpWord
 * Copyright (c) 2011  Commtap CIC
 * 
 * Please see the accompanying license.txt file (in the same directory as this
 * file) for the licensing conditions for the use of this software.
 * 
 */

// Functions for converting and adding HTML into PhpWord objects
// for creating a docx document.

/**
 * 
 * These are the elements which can be processed by this converter
 * 
 * This will tell us when to stop when parsing HTML.
 * Anything still remaining after a stop (i.e. no more
 * parsable tags) to be returned as is (with any tags filtered out).
 * 
 * @param string $tag
 *   (optional) - the tag for the element for which
 *   its possible children are required.
 * @return
 *   array of allowed children
 */
function htmltodocx_html_allowed_children($tag = NULL) {

  $allowed_children = array(
    'body' => array('p', 'ul', 'ol', 'table', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'),
    'h1' => array('a', 'em', 'i', 'strong', 'b', 'br', 'span', 'code', 'u', 'sup', 'text'),
    'h2' => array('a', 'em', 'i', 'strong', 'b', 'br', 'span', 'code', 'u', 'sup', 'text'),
    'h3' => array('a', 'em', 'i', 'strong', 'b', 'br', 'span', 'code', 'u', 'sup', 'text'),
    'h4' => array('a', 'em', 'i', 'strong', 'b', 'br', 'span', 'code', 'u', 'sup', 'text'),
    'h5' => array('a', 'em', 'i', 'strong', 'b', 'br', 'span', 'code', 'u', 'sup', 'text'),
    'h6' => array('a', 'em', 'i', 'strong', 'b', 'br', 'span', 'code', 'u', 'sup', 'text'),
    'p' => array('a', 'em', 'i', 'strong', 'b', 'ul', 'ol', 'img', 'table', 'br', 'span', 'code', 'u', 'sup', 'text', 'div', 'p'), // p does not nest - simple_html_dom will create a flat set of paragraphs if it finds nested ones.
    'div' => array('a', 'em', 'i', 'strong', 'b', 'ul', 'ol', 'img', 'table', 'br', 'span', 'code', 'u', 'sup', 'text', 'div', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'),
    'a' => array('text'), // PhpWord doesn't allow elements to be placed in link elements
    'em' => array('a', 'strong', 'b', 'br', 'span', 'code', 'u', 'sup', 'text'), // Italic
    'i' => array('a', 'strong', 'b', 'br', 'span', 'code', 'u', 'sup', 'text'), // Italic
    'strong' => array('a', 'em', 'i', 'br', 'span', 'code', 'u', 'sup', 'text'), // Bold
    'b' => array('a', 'em', 'i', 'br', 'span', 'code', 'u', 'sup', 'text'), // Bold
    'sup' => array('a', 'em', 'i', 'br', 'span', 'code', 'u', 'text'), // Superscript
    'u' => array('a', 'em', 'strong', 'b', 'i', 'br', 'span', 'code', 'sup', 'text'), // Underline - deprecated - but could be encountered.
    'ul' => array('li'),
    'ol' => array('li'),
    'li' => array('a', 'em', 'i', 'strong', 'b', 'ul', 'ol', 'img', 'br', 'span', 'code', 'u', 'sup', 'text'),
    'img' => array(),
    'table' => array('tbody', 'tr'),
    'tbody' => array('tr'),
    'tr' => array('td', 'th'),
    'td' => array('p', 'a', 'em', 'i', 'strong', 'b', 'ul', 'ol', 'img', 'br', 'span', 'code', 'u', 'sup', 'text', 'table'), // PhpWord does not allow you to insert a table into a table cell
    'th' => array('p', 'a', 'em', 'i', 'strong', 'b', 'ul', 'ol', 'img', 'br', 'span', 'code', 'u', 'sup', 'text', 'table'), // PhpWord does not allow you to insert a table into a table cell
    'br' => array(),
    'code' => array(), // Note, elements nested inside the code element do not work! (Perhaps simpleHTMLDom isn't recognising them).
    'span' => array('a', 'em', 'i', 'strong', 'b', 'img', 'br', 'span', 'code', 'sup', 'text'), // Used for styles - underline
    'text' => array(), // The tag name used for elements containing just text in SimpleHtmlDom.
	'footnote' => array('a', 'em', 'i', 'strong', 'b', 'ul', 'ol', 'img', 'table', 'br', 'span', 'code', 'u', 'sup', 'text', 'div', 'p'),
  );
  
  if (!$tag) {
    return $allowed_children;
  }
  elseif (isset($allowed_children[$tag])) {
    return $allowed_children[$tag];
  }
  else {
    return array();
  }
}

/**
 * Clean up text:
 * 
 * @param string $text
 * 
 */
function htmltodocx_clean_text($text) {
  
  // Replace each &nbsp; with a single space:
  $text = str_replace('&nbsp;', ' ', $text);
  if (strpos($text, '<') !== FALSE) {
    // We only run strip_tags if it looks like there might be some tags in the text
    // as strip_tags is expensive:
    $text = strip_tags($text);
  }
    
  // Strip out extra spaces:
  $text = preg_replace('/\s+/u', ' ', $text);
  
  // Convert entities:
  $text = html_entity_decode($text, ENT_COMPAT, 'UTF-8');
  return $text;
}

/**
 * Compute the styles that should be applied for the 
 * current element.
 * We start with the default style, and successively override
 * this with the current style, style set for the tag, classes
 * and inline styles.
 * 
 */
function _htmltodocx_get_style($element, $state) {

  $style_sheet = $state['style_sheet'];
  
  // Get the default styles
  $phpword_style = $style_sheet['default'];
  
  // Update with the current style
  $current_style = $state['current_style'];
  
  // Remove uninheritable items:
  $inheritable_props = htmltodocx_inheritable_props();
  foreach ($current_style as $property => $value) {
    if (!in_array($property, $inheritable_props)) {
      unset($current_style[$property]); 
    }
  }
  
  $phpword_style = array_merge($phpword_style, $current_style);
  
  // Update with any styles defined by the element tag
  $tag_style = isset($style_sheet['elements'][$element->tag]) ? $style_sheet['elements'][$element->tag] : array();
  $phpword_style = array_merge($phpword_style, $tag_style);
  
  // Find any classes defined for this element:
  $class_list = array();
  if (!empty($element->class)) {
    $classes = explode(' ', $element->class);
    foreach ($classes as $class) {
      $class_list[] = trim($class);
    } 
  }
  
  // Look for any style definitions for these classes:
  $classes_style = array();
  if (!empty($class_list) && !empty($style_sheet['classes'])) {
    foreach ($style_sheet['classes'] as  $class => $attributes) {
      if (in_array($class, $class_list)) {
        $classes_style = array_merge($classes_style, $attributes); 
      }
    }
  }
  
  $phpword_style = array_merge($phpword_style, $classes_style);
  
  // Find any inline styles:
  $inline_style_list = array();
  if (!empty($element->attr['style'])) {
    $inline_styles = explode(';', rtrim(rtrim($element->attr['style']), ';'));
    foreach ($inline_styles as $inline_style) {
      $style_pair = explode(':', $inline_style); 
      $inline_style_list[] = trim($style_pair[0]) . ': ' . trim($style_pair[1]);
    }
  }
  
  // Look for style definitions of these inline styles:
  $inline_styles = array();
  if (!empty($inline_style_list) && !empty($style_sheet['inline'])) {
    foreach ($style_sheet['inline'] as  $inline_style => $attributes) {
      if (in_array($inline_style, $inline_style_list)) {
        $inline_styles = array_merge($inline_styles, $attributes); 
      }
    } 
  }
  
  $phpword_style = array_merge($phpword_style, $inline_styles);
  
  return $phpword_style; 
}

/**
 * PhpWord style properties which are inheritable for the purposes of our
 * conversion:
 * 
 */
function htmltodocx_inheritable_props() {
  return array(
    'size',
    'name',
    'bold',
    'italic',
    'superScript',
    'subScript',
    'underline',
    'color',
    'fgColor',
    'align',
    'spacing',
    'listType',
  );
}


/**
 * Wrapper for htmltodocx_insert_html_recursive()
 * - inserts the initial defaults.
 * 
 * @param $phpword_object
 *   PhpWord object
 * @param mixed $html_dom_array
 *   SimpleHTMLDom object
 * @param mixed $state
 *   State
 */
 
 /*
     Keiko:
	 
	 Since, depending on image the page orientation can change, 
	 sections created in this code. Thus method parameters and body changed.
	 
	 phpword_object should be of type PhpWord()
 */
function htmltodocx_insert_html(&$phpword_object, $html_dom_array, &$state = array())
{
  
  // Set up initial defaults:
  
  // Lists:
  $state['pseudo_list'] = TRUE; 
  // This converter only supports "pseudo" lists at present.
  
  $state['pseudo_list_indicator_font_name'] = isset($state['pseudo_list_indicator_font_name']) ? $state['pseudo_list_indicator_font_name'] : 'Wingdings'; // Bullet indicator font
  $state['pseudo_list_indicator_font_size'] = isset($state['pseudo_list_indicator_font_size']) ? $state['pseudo_list_indicator_font_size'] : '7'; // Bullet indicator size
  $state['pseudo_list_indicator_character'] = isset($state['pseudo_list_indicator_character']) ? $state['pseudo_list_indicator_character'] : 'l '; // Gives a circle bullet point with wingdings
  
  // "Style sheet":
  $state['style_sheet'] = isset($state['style_sheet']) ? $state['style_sheet'] : array();
  $state['style_sheet']['default'] = isset($state['style_sheet']['default']) ? $state['style_sheet']['default'] : array();
  
  // Current style:
  $state['current_style'] = isset($state['current_style']) ? $state['current_style'] : array('size' => '11');
  
  // Parents:
  $state['parents'] = isset($state['parents']) ? $state['parents'] : array(0 => 'body');
  $state['list_depth'] = isset($state['list_depth']) ? $state['list_depth'] : 0;
  $state['context'] = isset($state['context']) ? $state['context'] : 'section'; 
  // Possible values - section, footer or header.
  
  // Tables:
  if (in_array('td', $state['parents']) || in_array('th', $state['parents']) || (isset($state['table_allowed']) && !$state['table_allowed']))
  {
    $state['table_allowed'] = FALSE;
  }
  else 
  {
    $state['table_allowed'] = TRUE;
  }
  
  // Headings option:
  $state['structure_document'] = isset($state['structure_document']) ? $state['structure_document'] : FALSE;
  
  if ($state['structure_document']) 
  {
    $state['structure_headings'] = array('h1' => 1, 'h2' => 2, 'h3' => 3, 'h4' => 4, 'h5' => 5, 'h6' => 6);
  }
  if (!$state['structure_document'] || !isset($state['table_of_contents_id'])) 
  {
    $state['table_of_contents_id'] = FALSE;
  }
  
  // Treatment of divs:
  // The default is to treat a div like a paragraph - that is we insert a new
  // line each time we encounter a new div.
  $state['treat_div_as_paragraph'] = isset($state['treat_div_as_paragraph']) ? $state['treat_div_as_paragraph'] : TRUE;

  // Recurse through the HTML Dom inserting elements into the phpword object as
  // we go:
  htmltodocx_insert_html_recursive($phpword_object, NULL,  $html_dom_array, $state);
}

/**
 * Populate PhpWord element
 * This recursive function processes all the elements and child elements
 * from the DOM array of objects created by SimpleHTMLDom.
 * 
 * @param object phpword_object
 *   PhpWord object to create sections 
 * @param object phpword_element
 *   PhpWord object to add in the converted html
 * @param array $html_dom_array
 *   Array of nodes generated by simple HTML dom
 * @param array $state
 *   Parameters for the current run
 */
 
 /*
     Keiko:
	 
	 Mathod htmltodocx_insert_html_recursive was changed.
	 
	 Since we need only to insert images which fit A4 page size,
	 A4 page sizes hardcoded here. Width and height of img tag
	 used only to define aspect ratio to save it in resizing process
	 
	 $space_after_set = FALSE; instead of TRUE
	 
	 Special tag pb was introduced. (PageBreak)
 */
function htmltodocx_insert_html_recursive(&$phpword_object, $phpword_element, $html_dom_array, &$state = array()) 
{
  $a4WidthPortraitOrientationInPoints = 595;
  $a4HeightPortraitOrientationInPoints = 842;  
  $a4WidthLandscapeOrientationInPoints = 842;
  $a4HeightLandscapeOrientationInPoints = 595;
   
  // Go through the html_dom_array, adding bits to go in the PhpWord element
  $allowed_children = htmltodocx_html_allowed_children($state['parents'][0]);

  
  foreach ($html_dom_array as $element) 
  {

  //var_dump($element->tag);
  
    $old_style = $state['current_style'];
    
    $state['current_style'] = _htmltodocx_get_style($element, $state);

    switch ($element->tag)
	{
      
      case 'p':
      case 'div': // Treat a div as a paragraph
      case 'h1':
      case 'h2':
      case 'h3':
      case 'h4':
      case 'h5':
      case 'h6':
      case 'center':
	  
        if ($state['structure_document'] && in_array($element->tag, array('h1', 'h2', 'h3', 'h4', 'h5', 'h6')) && is_object($state['phpword_object'])) 
		{		
          // If the structure_document option has been enabled, then headings
          // are used to create Word heading styles. Note, in this case, any
          // nested elements within the heading are displayed as text only.
          // Additionally we don't now add a text break after a heading where
          // sizeAfter has not been set.
		  
          $state['phpword_object']->addTitleStyle($state['structure_headings'][$element->tag], $state['current_style']);
		  if (empty($phpword_element)
		      || $phpword_element->getSettings()->getMarginTop() === 0)
		  {
		    $phpword_element = $phpword_object->createSection(); 
		  }
          $phpword_element->addTitle(htmltodocx_clean_text($element->innertext), $state['structure_headings'][$element->tag]);
          break;
        }
        
        if ($element->tag == 'div' && $state['table_of_contents_id'] && $element->id == $state['table_of_contents_id']) 
		{
          // Replace this div with a table of contents:
		  if (empty($phpword_element)
		      || $phpword_element->getSettings()->getMarginTop() === 0)
		  {
		    $phpword_element = $phpword_object->createSection(); 
		  }
          $phpword_element->addTOC($state['current_style'], $state['current_style']);
          break;
        }
	  
        // Everything in this element should be in the same text run
        // we need to initiate a text run here and pass it on. Starting one of
        // these elements will cause a new line to be added in the Word
        // document. In the case of divs this might not always be what is
        // wanted the setting 'treat_div_as_paragraph' determines whether or
        // not to add new lines for divs.
        if ($element->tag != 'div' || $state['treat_div_as_paragraph'] || !isset($state['textrun'])) 
		{
		  if (empty($phpword_element)
		      || $phpword_element->getSettings()->getMarginTop() === 0)
		  {
		    $phpword_element = $phpword_object->createSection(); 
		  }
          $state['textrun'] = $phpword_element->createTextRun($state['current_style']);
        }
        
        // For better usability for the end user of the Word document, we 
        // separate paragraphs and headings with an empty line. You can 
        // override this behaviour by setting the spaceAfter parameter for
        // the current element. 
        
        // If the spaceAfter parameter is not set, we set it temporarily to 0 
        // here and record that it wasn't set in the style. Later we will add
        // an empty line. Word 2007 and later have a non-zero default for
        // paragraph separation, so without setting that spacing to 0 here we
        // would end up with a large gap between paragraphs (the document
        // template default plus the extra line).
        $space_after_set = FALSE;
        if (!isset($state['current_style']['spaceAfter'])) 
		{
          $state['current_style']['spaceAfter'] = 0;
          $space_after_set = FALSE;
        }
        
        if (in_array($element->tag, $allowed_children))
		{
          array_unshift($state['parents'], $element->tag);
          $phpword_element_returned = htmltodocx_insert_html_recursive($phpword_object, $phpword_element, $element->nodes, $state);
		  if (!empty($phpword_element)
		      && ($phpword_element_returned->getSettings()->getOrientation() !== $phpword_element->getSettings()->getOrientation()))
		  {
		    $phpword_element = $phpword_object->createSection(array('orientation'  => $phpword_element->getSettings()->getOrientation()));
		  }
		  else
		  {
		    $phpword_element = $phpword_element_returned; 
		  }
          array_shift($state['parents']);
        }
        else
		{
          $state['textrun']->addText(htmltodocx_clean_text($element->innertext),  $state['current_style']);
        }
        unset($state['textrun']);
        if (!$space_after_set)
		{
          // Add the text break here - where the spaceAfter parameter hadn't
          // been set initially - also unset the spaceAfter parameter we just
          // set:
          //$phpword_element->addTextBreak();
          unset($state['current_style']['spaceAfter']);
        }
      break;

      case 'table':
        if (in_array('table', $allowed_children))
		{
          $old_table_state = $state['table_allowed'];
          if (!$state['table_allowed'] || in_array('td', $state['parents']) || in_array('th', $state['parents']))
		  {
            $state['table_allowed'] = FALSE; // This is a PhpWord constraint
          }
          else
		  {
            $state['table_allowed'] = TRUE;
            // PhpWord allows table_styles to be passed in a couple of
            // different ways either using an array of properties, or by
            // defining a full table style on the PhpWord object:
            if (is_object($state['phpword_object']) && method_exists($state['phpword_object'], 'addTableStyle'))
			{
              $state['phpword_object']->addTableStyle('temp_table_style', $state['current_style']);
              $table_style = 'temp_table_style';
            }
            else
			{
              $table_style = $state['current_style'];
            }
			if (empty($phpword_element)
			    || $phpword_element->getSettings()->getMarginTop() === 0)
		    {
		      $phpword_element = $phpword_object->createSection(); 
		    }
            $state['table'] = $phpword_element->addTable($table_style); 
          }
          array_unshift($state['parents'], 'table');
          $phpword_element_returned = htmltodocx_insert_html_recursive($phpword_object, $phpword_element, $element->nodes, $state);
		  if (!empty($phpword_element)
		      && ($phpword_element_returned->getSettings()->getOrientation() !== $phpword_element->getSettings()->getOrientation()))
		  {
		    $phpword_element = $phpword_object->createSection(array('orientation'  => $phpword_element->getSettings()->getOrientation()));
		  }
		  else
		  {
		    $phpword_element = $phpword_element_returned; 
		  }
          array_shift($state['parents']);
          // Reset table state to what it was before a table was added:
          $state['table_allowed'] = $old_table_state; 
          $phpword_element->addTextBreak(); 
        }
        else
		{
		  if (empty($phpword_element)
		      || $phpword_element->getSettings()->getMarginTop() === 0)
		  {
		    $phpword_element = $phpword_object->createSection(); 
		  }
          $state['textrun'] = $phpword_element->createTextRun();
          $state['textrun']->addText(htmltodocx_clean_text($element->innertext),  $state['current_style']);
        }     
      break;
      
      case 'tbody':
        if (in_array('tbody', $allowed_children))
		{
          array_unshift($state['parents'], 'tbody');
          $phpword_element_returned = htmltodocx_insert_html_recursive($phpword_object, $phpword_element, $element->nodes, $state);
		  if (!empty($phpword_element)
		      && ($phpword_element_returned->getSettings()->getOrientation() !== $phpword_element->getSettings()->getOrientation()))
		  {
		    $phpword_element = $phpword_object->createSection(array('orientation'  => $phpword_element->getSettings()->getOrientation()));
		  }
		  else
		  {
		    $phpword_element = $phpword_element_returned; 
		  }
          array_shift($state['parents']); 
        }
        else
		{
		  if (empty($phpword_element)
		      || $phpword_element->getSettings()->getMarginTop() === 0)
		  {
		    $phpword_element = $phpword_object->createSection(); 
		  }
          $state['textrun'] = $phpword_element->createTextRun();
          $state['textrun']->addText(htmltodocx_clean_text($element->innertext),  $state['current_style']);
        }
      break;
      
      case 'tr':
        if (in_array('tr', $allowed_children)) 
		{
          if ($state['table_allowed']) 
		  {
            $state['table']->addRow();
          }
          else 
		  {
            // Simply add a new line if a table is not possible in this
            // context:
			if (empty($phpword_element)
			    || $phpword_element->getSettings()->getMarginTop() === 0)
	  	    {
		      $phpword_element = $phpword_object->createSection(); 
		    }
            $state['textrun'] = $phpword_element->createTextRun();
          }
          array_unshift($state['parents'], 'tr');
          $phpword_element_returned = htmltodocx_insert_html_recursive($phpword_object, $phpword_element, $element->nodes, $state);
		  if (!empty($phpword_element)
		      && ($phpword_element_returned->getSettings()->getOrientation() !== $phpword_element->getSettings()->getOrientation()))
		  {
		    $phpword_element = $phpword_object->createSection(array('orientation'  => $phpword_element->getSettings()->getOrientation()));
		  }
		  else
		  {
		    $phpword_element = $phpword_element_returned; 
		  }
          array_shift($state['parents']); 
        }
        else 
		{
		  if (empty($phpword_element)
		      || $phpword_element->getSettings()->getMarginTop() === 0)
		  {
		    $phpword_element = $phpword_object->createSection(); 
		  }
          $state['textrun'] = $phpword_element->createTextRun();
          $state['textrun']->addText(htmltodocx_clean_text($element->innertext),  $state['current_style']);
        }     
      break;

      case 'td':
      case 'th':
        if (in_array($element->tag, $allowed_children) && $state['table_allowed']) 
		{
          unset($state['textrun']);
          if (isset($state['current_style']['width']))
		  {
            $cell_width = $state['current_style']['width'];
          }
          elseif (isset($element->width)) 
		  {
            $cell_width = $element->width * 15; 
            // Converting at 15 TWIPS per pixel.
          }
          else
		  {
            $cell_width = 800;
          }
          $state['table_cell'] = $state['table']->addCell($cell_width, $state['current_style']);
          array_unshift($state['parents'], $element->tag);
          $phpword_element_returned = htmltodocx_insert_html_recursive($phpword_object, $phpword_element, $state['table_cell'], $element->nodes, $state);
		  if (!empty($phpword_element)
		      && ($phpword_element_returned->getSettings()->getOrientation() !== $phpword_element->getSettings()->getOrientation()))
		  {
		    $phpword_element = $phpword_object->createSection(array('orientation'  => $phpword_element->getSettings()->getOrientation()));
		  }
		  else
		  {
		    $phpword_element = $phpword_element_returned; 
		  }
          array_shift($state['parents']); 
        }
        else 
		{
          if (!isset($state['textrun']))
		  {
		    if (empty($phpword_element)
			    || $phpword_element->getSettings()->getMarginTop() === 0)
		    {
		      $phpword_element = $phpword_object->createSection(); 
		    }
            $state['textrun'] = $phpword_element->createTextRun();
          }
          $state['textrun']->addText(htmltodocx_clean_text($element->innertext),  $state['current_style']);
        }
      break;
      
      case 'a':
	    
/*		var_dump($element->href);
		var_dump(get_class($state['textrun']));
		if($phpword_element instanceof \PhpOffice\PhpWord\Element\Footnote)
		{
			var_dump($phpword_element instanceof MyClass);
		}*/
        // Create a new text run if we aren't in one already:
        if (!isset($state['textrun']))
		{
		  if (empty($phpword_element)
		      || $phpword_element->getSettings()->getMarginTop() === 0)
		  {
		    $phpword_element = $phpword_object->createSection(); 
		  }
          $state['textrun'] = $phpword_element->createTextRun();
        }
        if ($state['context'] == 'section')
		{
          
          if (strpos($element->href, 'http://') === 0)
		  {
            $href = $element->href;
          }
          else if (strpos($element->href, 'https://') === 0)
		  {
            $href = $element->href;
          }		  
          elseif (strpos($element->href, '/') === 0) 
		  {
            $href = $state['base_root'] . $element->href;
          }
          else 
		  {
            $href = $state['base_root'] . $state['base_path'] . $element->href; 
          }
          // Replace any spaces in url with %20 - to prevent errors in the Word
          // document:
          $state['textrun']->addLink(htmltodocx_url_encode_chars($href), htmltodocx_clean_text($element->innertext), $state['current_style']);
        }
        else 
		{
          // Links can't seem to be included in headers or footers with
          // PHPWord: trying to include them causes an error which stops Word
          // from opening the file - in Word 2003 with the converter at least.
          // So add the link styled as a link only.
          $state['textrun']->addText(htmltodocx_clean_text($element->innertext), $state['current_style']);
        }
      break;
      
      case 'ul':
        $state['list_total_count'] = count($element->children);
        // We use this to be able to add the ordered list spaceAfter onto the
        // last list element. All ol children should be li elements.
        _htmltodocx_add_list_start_end_spacing_style($state);
        $state['list_number'] = 0; // Reset list number.
        if (in_array('ul', $allowed_children)) 
		{
          if (!isset($state['pseudo_list']))
		  {
            // Unset any existing text run:
            unset($state['textrun']);
            // PhpWord lists cannot appear in a text run. If we leave a text
            // run active then subsequent text will go in that text run (if it
            // isn't re-initialised), which would mean that text after this
            // list would appear before it in the Word document.
          }
          array_unshift($state['parents'], 'ul');
          $phpword_element_returned = htmltodocx_insert_html_recursive($phpword_object, $phpword_element, $element->nodes, $state);
		  if (!empty($phpword_element)
		      && ($phpword_element_returned->getSettings()->getOrientation() !== $phpword_element->getSettings()->getOrientation()))
		  {
		    $phpword_element = $phpword_object->createSection(array('orientation'  => $phpword_element->getSettings()->getOrientation()));
		  }
		  else
		  {
		    $phpword_element = $phpword_element_returned; 
		  }
          array_shift($state['parents']);
        }
        else 
		{
		  if (empty($phpword_element)
		      || $phpword_element->getSettings()->getMarginTop() === 0)
		  {
		    $phpword_element = $phpword_object->createSection(); 
		  }
          $state['textrun'] = $phpword_element->createTextRun();
          $state['textrun']->addText(htmltodocx_clean_text($element->innertext),  $state['current_style']);
        }
      break;
      
      case 'ol':
        $state['list_total_count'] = count($element->children); 
        // We use this to be able to add the ordered list spaceAfter onto the
        // last list element. All ol children should be li elements.
        _htmltodocx_add_list_start_end_spacing_style($state);
        $state['list_number'] = 0; // Reset list number. 
        if (in_array('ol', $allowed_children)) 
		{
          if (!isset($state['pseudo_list'])) 
		  {
            // Unset any existing text run:
            unset($state['textrun']); 
            // Lists cannot appear in a text run. If we leave a text run active
            // then subsequent text will go in that text run (if it isn't 
            // re-initialised), which would mean that text after this list
            // would appear before it in the Word document.
          }
          array_unshift($state['parents'], 'ol');
          $phpword_element_returned = htmltodocx_insert_html_recursive($phpword_object, $phpword_element, $element->nodes, $state);
		  if (!empty($phpword_element)
		      && ($phpword_element_returned->getSettings()->getOrientation() !== $phpword_element->getSettings()->getOrientation()))
		  {
		    $phpword_element = $phpword_object->createSection(array('orientation'  => $phpword_element->getSettings()->getOrientation()));
		  }
		  else
		  {
		    $phpword_element = $phpword_element_returned; 
		  }
          array_shift($state['parents']);
        }
        else 
		{
		  if (empty($phpword_element)
		      || $phpword_element->getSettings()->getMarginTop() === 0)
		  {
		    $phpword_element = $phpword_object->createSection(); 
		  }
          $state['textrun'] = $phpword_element->createTextRun();
          $state['textrun']->addText(htmltodocx_clean_text($element->innertext),  $state['current_style']);
        }
      break;
      
      case 'li': 
        // You cannot style individual pieces of text in a list element so we do it
        // with text runs instead. This does not allow us to indent lists at all, so
        // we can't show nesting.
        
        // Before and after spacings:
        if ($state['list_number'] === 0) 
		{
          $state['current_style'] = array_merge($state['current_style'], $state['list_style_before']); 
        }
        $last_item = FALSE;
        if ($state['list_number'] == $state['list_total_count'] - 1) 
		{
          $last_item = TRUE;
          if (empty($state['list_style_after'])) 
		  {
            $state['current_style']['spaceAfter'] = 0;
            // Set to 0 if not defined so we can add a text break without
            // ending up within too much space in Word2007+.
            // *Needs further testing on Word 2007+*
          }
          $state['current_style'] = array_merge($state['current_style'], $state['list_style_after']); 
        }

        // We create a new text run for each element:
		if (empty($phpword_element)
		    || $phpword_element->getSettings()->getMarginTop() === 0)
		{
		  $phpword_element = $phpword_object->createSection(); 
		}
        $state['textrun'] = $phpword_element->createTextRun($state['current_style']);
        
        if (in_array('li', $allowed_children)) 
		{
          $state['list_number']++;
          if ($state['parents'][0] == 'ol') 
		  {
            $item_indicator = $state['list_number'] . '. ';
            $style = $state['current_style'];
          }
          else
		  {
            $style = $state['current_style'];
            $style['name'] = $state['pseudo_list_indicator_font_name']; 
            $style['size'] = $state['pseudo_list_indicator_font_size'];
            $item_indicator = $state['pseudo_list_indicator_character'];
          }
          array_unshift($state['parents'], 'li');
          $state['textrun']->addText($item_indicator, $style);
          $phpword_element_returned = htmltodocx_insert_html_recursive($phpword_object, $phpword_element, $element->nodes, $state);
		  if (!empty($phpword_element)
		      && ($phpword_element_returned->getSettings()->getOrientation() !== $phpword_element->getSettings()->getOrientation()))
		  {
		    $phpword_element = $phpword_object->createSection(array('orientation'  => $phpword_element->getSettings()->getOrientation()));
		  }
		  else
		  {
		    $phpword_element = $phpword_element_returned; 
		  }
          array_shift($state['parents']);          
        }
        else 
		{
          $state['textrun']->addText(htmltodocx_clean_text($element->innertext),  $state['current_style']);
        }
        if ($last_item && empty($state['list_style_after']))
		{
		  if (empty($phpword_element)
		      || $phpword_element->getSettings()->getMarginTop() === 0)
		  {
		    $phpword_element = $phpword_object->createSection(); 
		  }
          $phpword_element->addTextBreak(); 
          // Add an empty line after the list if no spacing after has been
          // defined.
        }
        unset($state['textrun']);
      break;
      
      case 'text':
      // We may get some empty text nodes - containing just a space - in
      // simple HTML dom - we want to exclude those, as these can cause extra
      // line returns. However we don't want to exclude spaces between styling
      // elements (these will be within a text run).
        if (!isset($state['textrun'])) 
		{
          $text = htmltodocx_clean_text(trim($element->innertext));
        }
        else 
		{
          $text = htmltodocx_clean_text($element->innertext);
        }
        if (!empty($text)) 
		{
          if (!isset($state['textrun']))
		  {
		    if (empty($phpword_element))
		    {
		      $phpword_element = $phpword_object->createSection(); 
		    }
            $state['textrun'] = $phpword_element->createTextRun();
          }
          $state['textrun']->addText($text, $state['current_style']);
        }
      break;
      
      // Style tags:
      case 'strong':
      case 'b':
      case 'sup': // Not working in PHPWord
      case 'em':
      case 'i':
      case 'u':      
      case 'span':
      case 'code':
        
        // Create a new text run if we aren't in one already:
        if (!isset($state['textrun'])) 
		{
		  if (empty($phpword_element)
		      || $phpword_element->getSettings()->getMarginTop() === 0)
		  {
		    $phpword_element = $phpword_object->createSection(); 
		  }
          $state['textrun'] = $phpword_element->createTextRun();
        }
        if (in_array($element->tag, $allowed_children)) 
		{
          array_unshift($state['parents'], $element->tag);
          $phpword_element_returned = htmltodocx_insert_html_recursive($phpword_object, $phpword_element, $element->nodes, $state);
		  if (!empty($phpword_element)
		      && ($phpword_element_returned->getSettings()->getOrientation() !== $phpword_element->getSettings()->getOrientation()))
		  {
		    $phpword_element = $phpword_object->createSection(array('orientation'  => $phpword_element->getSettings()->getOrientation()));
		  }
		  else
		  {
		    $phpword_element = $phpword_element_returned; 
		  }
          array_shift($state['parents']);
        }
        else
		{
          $state['textrun']->addText(htmltodocx_clean_text($element->innertext), $state['current_style']);
        }
      break;
      
      // NB, Simple HTML Dom might not be picking up <br> tags.
      case 'br':
        // Simply create a new text run: 
		if (empty($phpword_element)
		    || $phpword_element->getSettings()->getMarginTop() === 0)
		{
		  $phpword_element = $phpword_object->createSection(); 
		}
        $state['textrun'] = $phpword_element->createTextRun();
      break;
      
      case 'img':
        $image_style = array();
		if (strpos($element->src, $state['base_root']) === 0)
		{
          // The image source is a full url, but nevertheless it is on this
          // server.
          $element_src = substr($element->src, strlen($state['base_root']));
        }
        else 
		{
          $element_src = $element->src;
        }
        
        if (strpos($element_src, 'http://') === 0) 
		{
          // The image url is from another site. Most probably the image won't
          // appear in the Word document.
          $src = $element_src;
        }
		elseif (strpos($element_src, 'https://') === 0) 
		{
          // The image url is from another site. Most probably the image won't
          // appear in the Word document.
          $src = $element_src;
        }
        elseif (strpos($element_src, '/') === 0) 
		{
          $src = $element_src;
        }
        else 
		{
          $src = htmltodocx_doc_root() . $state['base_path'] . $element_src; 
        }
		
		
		
		//if (file_exists($src))
	    if (file_get_contents($src, 0, null, 0, 1))
		{
			$switchFromPortraitToLandscape = FALSE;
			if ($element->height && $element->width) 
			{
			  $aspectRatio = $element->width/$element->height;
			  if ($aspectRatio > 1.0)
			  {
				$phpword_element = $phpword_object->createSection(array('orientation'  => 'landscape',
																			  'marginLeft'   => 0, 
																			  'marginRight'  => 0,
																			  'marginTop'    => 0,
																			  'marginBottom' => 0));
				$state['current_style']['width']  = $a4WidthLandscapeOrientationInPoints; 
				$state['current_style']['height'] = $a4WidthLandscapeOrientationInPoints/$aspectRatio;
				$switchFromPortraitToLandscape = TRUE;
			  }
			  else
			  {
				$phpword_element = $phpword_object->createSection(array('orientation'  => 'portrait',
																			  'marginLeft'   => 0, 
																			  'marginRight'  => 0,
																			  'marginTop'    => 0,
																			  'marginBottom' => 0));
				$state['current_style']['height'] = $a4HeightPortraitOrientationInPoints;
				$state['current_style']['width']  = $a4HeightPortraitOrientationInPoints*$aspectRatio;
			  }
			}
			else
			{
			  $phpword_element = $phpword_object->createSection(array('orientation'  => 'portrait',
																			'marginLeft'   => 0, 
																			'marginRight'  => 0,
																			'marginTop'    => 0,
																			'marginBottom' => 0));
			  $state['current_style']['height'] = $a4HeightPortraitOrientationInPoints;
			  $state['current_style']['width']  = $a4WidthPortraitOrientationInPoints;
			}
			
			if ($switchFromPortraitToLandscape === TRUE)  // Probably just $switchFromPortraitToLandscape but I'm too lazy to check Php abilities
			{
			  if (empty($phpword_element))
			  {
				$phpword_element = $phpword_object->createSection(); 
			  }
			  $phpword_element->addImage($src, $state['current_style']);
			}
			else
			{
			  if (empty($phpword_element))
			  {
				$phpword_element = $phpword_object->createSection(); 
			  }
			  $phpword_element->addImage($src, $state['current_style']);
			}
	    }
      break;
	  
	  case 'pb':
	    if (empty($phpword_element))
		{
		  $phpword_element = $phpword_object->createSection(); 
		}
		$phpword_element->addPageBreak();
	  break;

	  case 'footnote':
      
		if ($element->text)
		{
			$state['textrun'] = $phpword_element->createTextRun();
			$state['textrun']->addText(htmltodocx_clean_text($element->text),  $state['current_style']);
		}
		
		$textrunBeforeFootnote = $state['textrun'];
		$styleBeforeFootnote = $state['current_style'];
		$state['current_style'] = array();
		
		$state['textrun'] = $state['textrun']->addFootnote();
		
		array_unshift($state['parents'], $element->tag);
		$phpword_element_returned = htmltodocx_insert_html_recursive($phpword_object, $phpword_element, $element->nodes, $state);
		if (!empty($phpword_element)
			&& ($phpword_element_returned->getSettings()->getOrientation() !== $phpword_element->getSettings()->getOrientation()))
		{
		  $phpword_element = $phpword_object->createSection(array('orientation'  => $phpword_element->getSettings()->getOrientation()));
		}
		else
		{
		  $phpword_element = $phpword_element_returned; 
		}
		array_shift($state['parents']);
		
		$state['textrun'] = $textrunBeforeFootnote;
		$state['current_style'] = $styleBeforeFootnote;
		
		break;
		
      default:
	    if (empty($phpword_element))
		{
		  $phpword_element = $phpword_object->createSection(); 
		}
        $state['textrun'] = $phpword_element->createTextRun();
        $state['textrun']->addText(htmltodocx_clean_text($element->innertext),  $state['current_style']);
      break; 
    } 
    
    // Reset the style back to what it was:
    $state['current_style'] = $old_style;
  }
  if (empty($phpword_element))
  {
	$phpword_element = $phpword_object->createSection(); 
  }
  return $phpword_element;
}

/**
 * Before/after styles for list elements - recorded
 * for use by the first or last item in a list.
 * 
 */
function _htmltodocx_add_list_start_end_spacing_style(&$state) {
  
  $state['list_style_after'] = isset($state['current_style']['spaceAfter']) ? array('spaceAfter' => $state['current_style']['spaceAfter']) : array();
  
  $state['list_style_before'] = isset($state['current_style']['spaceBefore']) ? array('spaceBefore' => $state['current_style']['spaceBefore']) : array();
  
}

/**
 * Get the document root.
 * 
 */
function htmltodocx_doc_root() {
   
  $local_path = getenv("SCRIPT_NAME");
  // Should be available on both Apache and non Apache servers.
  // Should be available on both Apache and non Apache servers.
  
  $local_dir = substr($local_path, 0, strrpos($local_path, '/'));
  
  if (empty($local_dir)) {
    return realpath('');
  }
  else {
    return substr(realpath(''), 0, -1 * strlen($local_dir));
  }
}

/**
 * Encodes selected characters in a url to prevent errors in the created Word
 * document. Note: if there is a space in the url and there isn't a forward
 * slash preceding it at some point, the resulting Word document will be
 * corrupted (even where the space has been urlencoded). We convert spaces to
 * %20 which stops this corruption in circumstances where a forward slash is
 * present.
 * 
 */
function htmltodocx_url_encode_chars($url) {
  
  // List the characters in this array to be encoded:
  $encode_chars = array(' ');
  
  foreach ($encode_chars as $char) {
    $encoded_chars[] = rawurlencode($char);
  }
  
  $encoded_url = str_replace($encode_chars, $encoded_chars, $url);
  
  return $encoded_url;
}
