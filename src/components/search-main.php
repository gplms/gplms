    <!-- Main Content -->
    <div class="container py-4">
        <div class="search-container">
            <h2 class="mb-4 text-center"><i class="fas fa-search me-2"></i>Search Library Catalog</h2>
            
            <!-- Search Form -->
            <form id="searchForm" method="GET" action="search.php">
                <div class="input-group mb-4">
                    <input type="text" name="search" class="form-control form-control-lg" 
                           placeholder="Search by title..." 
                           value="<?= htmlspecialchars($search_query) ?>">
                    <button class="btn btn-primary btn-lg" type="submit">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                </div>
                
                <!-- Advanced Filters Section -->
                <div class="filters-section">
                    <h5 class="mb-3 d-flex align-items-center">
                        <i class="fas fa-filter me-2"></i>Advanced Filters
                        <button type="button" class="collapse-btn" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
                            <i class="fas fa-chevron-up collapse-icon"></i> Toggle
                        </button>
                        <a href="search.php" class="btn btn-sm btn-outline-secondary clear-filters">
                            <i class="fas fa-times me-1"></i> Clear Filters
                        </a>
                    </h5>
                    
                    <div class="collapse show" id="advancedFilters">
                        <div class="row">
                            <!-- Column 1 -->
                            <div class="col-md-4">
                                <div class="filter-group">
                                    <div class="filter-label">Material Type</div>
                                    <select name="type" class="form-select">
                                        <option value="">All Types</option>
                                        <?php foreach ($types as $type): ?>
                                            <option value="<?= $type ?>" <?= $type_filter === $type ? 'selected' : '' ?>>
                                                <?= $type ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <div class="filter-label">Publisher</div>
                                    <select name="publisher" class="form-select">
                                        <option value="">All Publishers</option>
                                        <?php foreach ($publishers as $publisher): ?>
                                            <option value="<?= $publisher ?>" <?= $publisher_filter === $publisher ? 'selected' : '' ?>>
                                                <?= $publisher ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <div class="filter-label">Author</div>
                                    <select name="author" class="form-select">
                                        <option value="">All Authors</option>
                                        <?php foreach ($authors as $author): ?>
                                            <option value="<?= $author ?>" <?= $author_filter === $author ? 'selected' : '' ?>>
                                                <?= $author ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Column 2 -->
                            <div class="col-md-4">
                                <div class="filter-group">
                                    <div class="filter-label">ISBN</div>
                                    <input type="text" name="isbn" class="form-control" 
                                           placeholder="Enter ISBN" 
                                           value="<?= htmlspecialchars($isbn_filter) ?>">
                                </div>
                                
                                <div class="filter-group">
                                    <div class="filter-label">ISSN</div>
                                    <input type="text" name="issn" class="form-control" 
                                           placeholder="Enter ISSN" 
                                           value="<?= htmlspecialchars($issn_filter) ?>">
                                </div>
                                
                                <div class="filter-group">
                                    <div class="filter-label">Category</div>
                                    <select name="category" class="form-select">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category ?>" <?= $category_filter === $category ? 'selected' : '' ?>>
                                                <?= $category ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Column 3 -->
                            <div class="col-md-4">
                                <div class="filter-group">
                                    <div class="filter-label">Publication Year</div>
                                    <select name="year" class="form-select">
                                        <option value="">All Years</option>
                                        <?php foreach ($years as $year): ?>
                                            <option value="<?= $year ?>" <?= $year_filter == $year ? 'selected' : '' ?>>
                                                <?= $year ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <div class="filter-label">Language</div>
                                    <select name="language" class="form-select">
                                        <option value="">All Languages</option>
                                        <?php foreach ($languages as $language): ?>
                                            <option value="<?= $language ?>" <?= $language_filter === $language ? 'selected' : '' ?>>
                                                <?= $language ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <div class="filter-label">Added By</div>
                                    <select name="added_by" class="form-select">
                                        <option value="">All Users</option>
                                        <?php foreach ($added_by_users as $user): ?>
                                            <option value="<?= $user ?>" <?= $added_by_filter === $user ? 'selected' : '' ?>>
                                                <?= $user ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Column 4 -->
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <div class="filter-label">Status</div>
                                    <select name="status" class="form-select">
                                        <option value="">All Statuses</option>
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= $status ?>" <?= $status_filter === $status ? 'selected' : '' ?>>
                                                <?= ucfirst($status) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <div class="filter-label">Sort By</div>
                                    <select name="sort" class="form-select">
                                        <option value="title" <?= $sort_by === 'title' ? 'selected' : '' ?>>Title (A-Z)</option>
                                        <option value="title_desc" <?= $sort_by === 'title_desc' ? 'selected' : '' ?>>Title (Z-A)</option>
                                        <option value="year" <?= $sort_by === 'year' ? 'selected' : '' ?>>Year (Oldest)</option>
                                        <option value="year_desc" <?= $sort_by === 'year_desc' ? 'selected' : '' ?>>Year (Newest)</option>
                                        <option value="added" <?= $sort_by === 'added' ? 'selected' : '' ?>>Added Date (Oldest)</option>
                                        <option value="added_desc" <?= $sort_by === 'added_desc' ? 'selected' : '' ?>>Added Date (Newest)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Results Section -->
            <div class="mt-4">
                <div class="pagination-info">
                    <div>
                        <i class="fas fa-books me-2"></i>
                        <?= $totalRecords ?> Material<?= $totalRecords !== 1 ? 's' : '' ?> Found
                        <small class="text-muted ms-2"><?= $search_query ? "Search: \"$search_query\"" : "All materials" ?></small>
                    </div>
                    <div>
                        Showing <?= min($recordsPerPage, count($materials)) ?> of <?= $totalRecords ?> items
                    </div>
                </div>
                
                <?php if (empty($materials)): ?>
                    <div class="alert alert-info text-center py-4">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <h4>No materials found</h4>
                        <p class="mb-0">Try adjusting your search criteria</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="results-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Authors</th>
                                        <th>Type</th>
                                        <th>Publisher</th>
                                        <th>Year</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materials as $item): 
                                        $icon_class = '';
                                        if (strpos($item['type_name'], 'Book') !== false) $icon_class = 'material-book';
                                        if (strpos($item['type_name'], 'Magazine') !== false) $icon_class = 'material-magazine';
                                        if (strpos($item['type_name'], 'Newspaper') !== false) $icon_class = 'material-newspaper';
                                        if (strpos($item['type_name'], 'Journal') !== false) $icon_class = 'material-journal';
                                        if (strpos($item['type_name'], 'Manuscript') !== false) $icon_class = 'material-manuscript';
                                    ?>
                                        <tr class="clickable-row" data-bs-toggle="modal" data-bs-target="#detailModal" data-item-id="<?= $item['item_id'] ?>">
                                            <td><?= $item['item_id'] ?></td>
                                            <td class="title-cell"><?= htmlspecialchars($item['title']) ?></td>
                                            <td><?= $item['authors'] ? htmlspecialchars(substr($item['authors'], 0, 30)) . (strlen($item['authors']) > 30 ? '...' : '') : 'N/A' ?></td>
                                            <td>
                                                <span class="badge badge-type">
                                                    <i class="fas fa-book material-icon <?= $icon_class ?>"></i>
                                                    <?= htmlspecialchars($item['type_name']) ?>
                                                </span>
                                            </td>
                                            <td><?= $item['publisher_name'] ? htmlspecialchars(substr($item['publisher_name'], 0, 15)) . (strlen($item['publisher_name']) > 15 ? '...' : '') : 'N/A' ?></td>
                                            <td><?= $item['publication_year'] ? htmlspecialchars($item['publication_year']) : 'N/A' ?></td>
                                            <td>
                                                <span class="status-badge status-<?= $item['status'] ?>">
                                                    <?= ucfirst($item['status']) ?>
                                                </span>
                                            </td>
                                            <td class="action-cell">
                                                <?php if (isset($_SESSION['user_id'])): ?>
                                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                                        <!-- Admin can edit/delete all items -->
                                                        <a href="edit.php?id=<?= $item['item_id'] ?>" class="action-btn btn-edit" title="Edit" onclick="event.stopPropagation()">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="delete.php?id=<?= $item['item_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to \ndelete this item?'); event.stopPropagation()">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <!-- For non-admin users (Librarians) -->
                                                        <?php if ($item['added_by'] == $_SESSION['user_id']): ?>
                                                            <!-- User can edit/delete their own items -->
                                                            <a href="edit.php?id=<?= $item['item_id'] ?>" class="action-btn btn-edit" title="Edit" onclick="event.stopPropagation()">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="delete.php?id=<?= $item['item_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to \ndelete this item?'); event.stopPropagation()">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <!-- Request buttons for others' items -->
                                                            <button class="action-btn btn-request" title="Request Edit" 
                                                                    onclick="requestEdit(<?= $item['item_id'] ?>, event)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="action-btn btn-request" title="Request Delete" 
                                                                    onclick="requestDelete(<?= $item['item_id'] ?>, event)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination controls -->
                        <div class="pagination-controls">
                            <nav>
                                <ul class="pagination">
                                    <!-- Previous button -->
                                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= buildPaginationUrl($currentPage - 1) ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <!-- Page numbers -->
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= buildPaginationUrl($i) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Next button -->
                                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= buildPaginationUrl($currentPage + 1) ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>