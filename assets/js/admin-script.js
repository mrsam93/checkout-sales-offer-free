document.addEventListener('DOMContentLoaded', function () {
    const productSearchInput = document.getElementById('ccs_free_product_search');
    const productResultsDiv = document.getElementById('ccs_free_product_results');
    const hiddenProductIdInput = document.getElementById('ccs_free_product_id');

    if (!productSearchInput || !productResultsDiv || !hiddenProductIdInput) return;

    let debounceTimeout;

    productSearchInput.addEventListener('input', function () {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(() => {
            const searchTerm = this.value;

            if (searchTerm.length < 2) {
                productResultsDiv.style.display = 'none';
                productResultsDiv.innerHTML = ''; // Clear previous results
                return;
            }

            // AJAX request
            fetch(ccs_ajax_free.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ccs_search_products_free',
                    search_term: searchTerm,
                    nonce: ccs_ajax_free.nonce,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    console.log("AJAX Response:", data);

                    if (data.success && data.data.products.length > 0) {
                        productResultsDiv.innerHTML = ''; // Clear previous results
                        data.data.products.forEach((product) => {
                            const productItem = document.createElement('div');
                            productItem.classList.add('ccs-product-item');
                            productItem.dataset.id = product.id;
                            productItem.textContent = product.name;
                            productResultsDiv.appendChild(productItem);
                        });
                        productResultsDiv.style.display = 'block';
                        productResultsDiv.style.border = '1px solid #ccc';
                        productResultsDiv.style.maxHeight = '200px';
                        productResultsDiv.style.overflowY = 'auto';
                    } else {
                        productResultsDiv.innerHTML = '<div class="no-results">محصولی یافت نشد</div>';
                        productResultsDiv.style.display = 'block';
                    }
                })
                .catch((error) => {
                    console.error("Fetch error:", error);
                    productResultsDiv.innerHTML = '<div class="error-message">خطا در برقراری ارتباط</div>';
                    productResultsDiv.style.display = 'block';
                });
        }, 300); // Debounce delay
    });

    productResultsDiv.addEventListener('click', function (e) {
        if (e.target.classList.contains('ccs-product-item')) {
            const selectedProductId = e.target.dataset.id;
            const selectedProductName = e.target.textContent;

            // Update the hidden input
            hiddenProductIdInput.value = selectedProductId;

            // Update the display
            productSearchInput.value = `${selectedProductName} (انتخاب شده)`;

            // Hide results
            productResultsDiv.style.display = 'none';
        }
    });
});
