jQuery(document).ready(function($) {
  const postId = $('#post_ID').val();

  $('#post input[type=submit]').on('click', function(event) {
    var generalSlugField = $('#general_slug');
    var titleField = $('#title');

    if (generalSlugField.val().trim() === '') {
      generalSlugField.val(titleField.val());
    }
    $('#post input[type=number]').each(function(index, el) {
      if ($(el).val().trim() === '' && $(el).attr('required') !== undefined && $(el).attr('data-default') !== undefined) {
        $(el).val($(el).attr('data-default'));
      }
    });
  })

  $('#copy_linkgenius_url').on('click', function(event) {
    event.preventDefault();
    var linkgenius_url = $("#linkgenius_url");
    text = linkgenius_url.attr('href');

    // Create a range object
    var range = document.createRange();
    // Select the text content of the anchor element
    range.selectNodeContents(linkgenius_url[0]);
    // Create a selection object
    var selection = window.getSelection();
    // Remove existing selections
    selection.removeAllRanges();
    // Add the new range to the selection
    selection.addRange(range);

    // Copy the text inside the text field
    if(navigator.clipboard !== undefined) {
      navigator.clipboard.writeText(text);
    }
    else {
      // deprecated backup method when no ssl is available
      document.execCommand('copy');
    }

  });

  $('#reset_clicks').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'linkgenius_reset_clicks',
            linkgenius_id: postId
        },
        success: function(response) {
            $('#clicks_label').text("0");
        },
        error: function(error) {
            console.log(error);
        }
    })
  });

  $('#search_link_locations').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: 'linkgenius_search_locations',
            linkgenius_id: postId
        },
        success: function(response) {
            $('#link_locations').html(JSON.parse(response));
        },
        error: function(error) {
            console.log(error);
        }
    })
  });
});