jQuery(document).ready(function($) {
    const $grid = $('.htk-hackathon-grid');
    const $categoryFilter = $('#htk-category-filter');

    // Handle category filter change
    $categoryFilter.on('change', function() {
        const category = $(this).val();
        filterHackathons(category);
    });

    function filterHackathons(category) {
        $grid.addClass('htk-loading');

        $.ajax({
            url: htkArchive.ajaxurl,
            type: 'POST',
            data: {
                action: 'htk_filter_hackathons',
                nonce: htkArchive.nonce,
                category: category
            },
            success: function(response) {
                if (response.success) {
                    // Update URL without reloading the page
                    const newUrl = category 
                        ? `${window.location.pathname}?project_category=${category}` 
                        : window.location.pathname;
                    window.history.pushState({}, '', newUrl);

                    // Update grid content
                    $grid.html(response.data.html);

                    // Update pagination
                    $('.htk-pagination').html(response.data.pagination);
                }
            },
            error: function() {
                console.error('Error filtering hackathons');
            },
            complete: function() {
                $grid.removeClass('htk-loading');
            }
        });
    }

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function() {
        const params = new URLSearchParams(window.location.search);
        const category = params.get('project_category') || '';
        $categoryFilter.val(category);
        filterHackathons(category);
    });
});