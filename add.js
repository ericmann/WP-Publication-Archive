/*
* This file is used by the upload and edit file forms to add new inputs for authors and keywords
*/

/*
* Function to add one new input keyword
*/
function addNewKeyWord(){

	setOuterHTML(document.getElementById("moreKeywords"), '<br /><input style="margin-top: 1px;" type="text" name="keywords[]" size="20" /><span id="moreKeywords">&nbsp;</span>' );
}

/*
* Function to add another input for authors
*/
function addNewAuthor(){

	setOuterHTML(document.getElementById("moreAuthors"), '<br /><input style="margin-top: 1px;" type="text" name="authors[]" size="20" /><span id="moreAuthors">&nbsp;</span>' );
}

/*
* Function to put the new input in the document
*/
function setOuterHTML(element, toValue)
{
	if (typeof(element.outerHTML) != 'undefined')
		element.outerHTML = toValue;
	else
	{
		var range = document.createRange();
		range.setStartBefore(element);
		element.parentNode.replaceChild(range.createContextualFragment(toValue), element);
	}
}										
										
										
				
