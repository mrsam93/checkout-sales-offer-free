jQuery(document).ready(function ($) {
    $('#ccs_product_search').on('input', function () {
        var searchTerm = $(this).val(); // Get the search term
        var resultsContainer = $('#ccs_product_results');

        // Clear results if input is empty
        if (!searchTerm.trim()) {
            resultsContainer.empty().hide();
            return;
        }

        // Show loader or feedback
        resultsContainer.html('<p>در حال جستجو...</p>').show();

        $.ajax({
            url: ccs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ccs_search_products',
                nonce: ccs_ajax.nonce, // Use localized nonce
                search_term: searchTerm,
            },
            success: function (response) {
                if (response.success) {
                    var products = response.data.products;
                    resultsContainer.empty();

                    if (products.length === 0) {
                        resultsContainer.html('<p>هیچ محصولی پیدا نشد.</p>');
                        return;
                    }

                    products.forEach(function (product) {
                        resultsContainer.append(
                            '<p data-product-id="' + product.id + '">' + product.name + '</p>'
                        );
                    });
                } else {
                    resultsContainer.html('<p>خطایی رخ داده است.</p>');
                }
            },
            error: function () {
                resultsContainer.html('<p>خطایی در جستجو رخ داده است.</p>');
            },
        });
    });

    // Select product
    $('#ccs_product_results').on('click', 'p', function () {
        var productId = $(this).data('product-id');
        var productName = $(this).text();

        // Set selected product
        $('#ccs_free_product_id').val(productId);
        $('#ccs_product_search').val(productName + ' (انتخاب شده)');
        $('#ccs_product_results').empty().hide(); // Clear and hide results
    });
});
