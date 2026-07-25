/**
 * Admin JavaScript for Primetime TCG Card Content plugin
 */

jQuery(document).ready(function($) {
    // Card search functionality
    var searchTimeout;
    $('#card-search-input').on('keyup', function() {
        var searchTerm = $(this).val();
        
        clearTimeout(searchTimeout);
        
        if (searchTerm.length < 2) {
            $('.card-search-results').empty();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $('.card-search-results').html('<div class="loading-indicator">Searching</div>');
            
            $.ajax({
                url: ptcc_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'search_cards',
                    search_term: searchTerm,
                    nonce: ptcc_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        displaySearchResults(response.data.results);
                    } else {
                        $('.card-search-results').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                    }
                },
                error: function() {
                    $('.card-search-results').html('<div class="notice notice-error"><p>An error occurred while searching. Please try again.</p></div>');
                }
            });
        }, 500);
    });
    
    // Display search results
    function displaySearchResults(results) {
        var $resultsContainer = $('.card-search-results');
        $resultsContainer.empty();
        
        if (results.length === 0) {
            $resultsContainer.html('<div class="notice notice-info"><p>' + ptcc_admin.strings.no_results + '</p></div>');
            return;
        }
        
        $.each(results, function(index, card) {
            var $result = $('<div class="card-search-result"></div>');
            
            // Card image
            $result.append('<div class="card-search-result-image"><img src="' + card.image + '" alt="' + card.name + '"></div>');
            
            // Card info
            var $info = $('<div class="card-search-result-info"></div>');
            $info.append('<div class="card-search-result-name">' + card.name + '</div>');
            $info.append('<div class="card-search-result-set">' + card.set + ' · #' + card.number + ' · ' + card.id + '</div>');
            
            // Card actions
            var $actions = $('<div class="card-search-result-actions"></div>');
            
            if (card.hasContent) {
                $actions.append('<a href="post.php?post=' + card.postId + '&action=edit" class="button">' + ptcc_admin.strings.edit_content + '</a>');
            } else {
                $actions.append('<button class="button button-primary create-content" data-card-id="' + card.id + '">' + ptcc_admin.strings.create_content + '</button>');
            }
            
            $actions.append('<a href="' + card.permalink + '" class="button" target="_blank">' + ptcc_admin.strings.view_card + '</a>');
            
            $info.append($actions);
            $result.append($info);
            
            $resultsContainer.append($result);
        });
    }
    
    // Card filters
    $('#card-filters-form').on('submit', function(e) {
        e.preventDefault();
        loadCards(1);
    });
    
    // Reset filters
    $('#filter-reset').on('click', function() {
        $('#filter-card-id').val('');
        $('#filter-set').val('all');
        $('#filter-type').val('all');
        $('#filter-status').val('all');
        loadCards(1);
    });
    
    // Load cards with filters
    function loadCards(page) {
        var $tableBody = $('.card-content-table tbody');
        $tableBody.html('<tr><td colspan="7" class="loading-indicator">' + ptcc_admin.strings.loading + '</td></tr>');
        
        $.ajax({
            url: ptcc_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'filter_cards',
                card_id: $('#filter-card-id').val(),
                set: $('#filter-set').val(),
                type: $('#filter-type').val(),
                status: $('#filter-status').val(),
                page: page,
                nonce: ptcc_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayCards(response.data.results);
                    displayPagination(response.data.pagination);
                } else {
                    $tableBody.html('<tr><td colspan="7" class="notice notice-error"><p>' + response.data.message + '</p></td></tr>');
                }
            },
            error: function() {
                $tableBody.html('<tr><td colspan="7" class="notice notice-error"><p>An error occurred while loading cards. Please try again.</p></td></tr>');
            }
        });
    }
    
    // Display cards in table
    function displayCards(cards) {
        var $tableBody = $('.card-content-table tbody');
        $tableBody.empty();
        
        if (cards.length === 0) {
            $tableBody.html('<tr><td colspan="7">' + ptcc_admin.strings.no_results + '</td></tr>');
            return;
        }
        
        $.each(cards, function(index, card) {
            var $row = $('<tr></tr>');
            
            // Card image
            $row.append('<td class="column-card_image"><img src="' + card.image + '" alt="' + card.name + '"></td>');
            
            // Card name
            $row.append('<td class="column-card_name">' + card.name + '</td>');
            
            // Card set
            $row.append('<td class="column-card_set">' + card.set + '</td>');
            
            // Card number
            $row.append('<td class="column-card_number">' + card.number + '</td>');
            
            // Card ID
            $row.append('<td class="column-card_id">' + card.id + '</td>');
            
            // Last updated
            $row.append('<td class="column-last_updated">' + (card.lastUpdated ? card.lastUpdated : '-') + '</td>');
            
            // Actions
            var $actions = $('<td class="column-actions"></td>');
            
            if (card.hasContent) {
                $actions.append('<a href="post.php?post=' + card.postId + '&action=edit" class="button">' + ptcc_admin.strings.edit_content + '</a>');
            } else {
                $actions.append('<button class="button button-primary create-content" data-card-id="' + card.id + '">' + ptcc_admin.strings.create_content + '</button>');
            }
            
            $actions.append('<a href="' + card.permalink + '" class="button" target="_blank">' + ptcc_admin.strings.view_card + '</a>');
            
            $row.append($actions);
            
            $tableBody.append($row);
        });
    }
    
    // Display pagination
    function displayPagination(pagination) {
        var $paginationStatus = $('.pagination-status');
        var $paginationLinks = $('.pagination-links');
        
        // Update status
        var start = ((pagination.current_page - 1) * pagination.per_page) + 1;
        var end = Math.min(start + pagination.per_page - 1, pagination.total);
        
        $paginationStatus.text(
            ptcc_admin.strings.pagination_status
                .replace('%1$s', start)
                .replace('%2$s', end)
                .replace('%3$s', pagination.total)
        );
        
        // Update links
        $paginationLinks.empty();
        
        // Previous page
        if (pagination.current_page > 1) {
            $paginationLinks.append('<a href="#" class="page-link" data-page="' + (pagination.current_page - 1) + '">&laquo; Previous</a>');
        }
        
        // Page numbers
        var startPage = Math.max(1, pagination.current_page - 2);
        var endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
        
        for (var i = startPage; i <= endPage; i++) {
            var $link = $('<a href="#" class="page-link" data-page="' + i + '">' + i + '</a>');
            
            if (i === pagination.current_page) {
                $link.addClass('current');
            }
            
            $paginationLinks.append($link);
        }
        
        // Next page
        if (pagination.current_page < pagination.total_pages) {
            $paginationLinks.append('<a href="#" class="page-link" data-page="' + (pagination.current_page + 1) + '">Next &raquo;</a>');
        }
    }
    
    // Pagination click handler
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        loadCards(page);
    });
    
    // Create content button click handler
    $(document).on('click', '.create-content', function() {
        var $button = $(this);
        var cardId = $button.data('card-id');
        
        $button.prop('disabled', true).text(ptcc_admin.strings.creating_content);
        
        $.ajax({
            url: ptcc_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'create_card_content',
                card_id: cardId,
                nonce: ptcc_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.edit_url;
                } else {
                    alert(response.data.message || ptcc_admin.strings.error_creating);
                    $button.prop('disabled', false).text(ptcc_admin.strings.create_content);
                }
            },
            error: function() {
                alert(ptcc_admin.strings.error_creating);
                $button.prop('disabled', false).text(ptcc_admin.strings.create_content);
            }
        });
    });
    
    // Load initial cards
    loadCards(1);
});
