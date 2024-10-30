jQuery(document).ready(function($) {
    $("#custom_free_type").on("change", function() {
        selectType = $(this).val();
        row = $(".cmb2-id-custom-free-default");
        parent = row.parent();
        row.remove();
        parent.append(linkgenius_prototypes[selectType]);
    });
});