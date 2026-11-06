$(document).on("click", "[data-feature='attach-image']", function (event) {
    event.preventDefault();

    var attach_btn = $(this);
    var upload_sel = attach_btn.attr('data-file-selector');
    var textarea_sel = attach_btn.attr('data-textarea');
    var textarea = $(textarea_sel);

    console.log(textarea);

    $(upload_sel).click();

    $(document).on("change", upload_sel, function (event) {
        event.preventDefault();

        var btn = $(this);
        var btntext = attach_btn.find('.btn-text');
        var prevbtntext = btntext.html();

        var file_data = $(upload_sel).prop('files')[0];
        var form_data = new FormData();

        if(!textarea || btn.hasClass('w3-disabled')) {
            return;
        }

        form_data.append('imagefile', file_data);
        form_data.append('upload', 'true');

        btntext.html('<img src="/img/loading.gif" style="width: 20px; height: 20px;" />');
        btn.prop("disabled", true);

        $.ajax({
            url: "../ajax/image",
            dataType: 'json',
            contentType: false,
            processData: false,
            data: form_data,
            type: 'POST',
            success: function(res) {
                btntext.html(prevbtntext);
                btn.prop("disabled", false);

                if (res && res.success && res.image && res.image.markdown) {
                    textarea.val(textarea.val() + ($.trim(textarea.val()).length === 0 ? "" : "\n") + res.image.markdown);
                } else if (res && res.message && !res.success) {
                    showError(res.message);
                }
            },
            error: function(xhr, text, error) {
                btntext.html(prevbtntext);
                btn.prop("disabled", false);

                showError(text + error);
            }
        });
    });
});