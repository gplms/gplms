<!-- Main Content -->
<div class="container py-4">
    <div class="search-container">
        <h2 class="mb-4 text-center"><i class="fas fa-search me-2"></i><?= $lang['search'] ?> <?= $lang['library_catalog'] ?></h2>
        
        <!-- Search Form -->
        <form id="searchForm" method="GET" action="search.php">
            <div class="input-group mb-4">
                <input type="text" name="search" class="form-control form-control-lg" 
                       placeholder="<?= $lang['search'] ?> <?= strtolower($lang['by_title']) ?>..." 
                       value="<?= htmlspecialchars($search_query) ?>">
                <button class="btn btn-primary btn-lg" type="submit">
                    <i class="fas fa-search me-1"></i> <?= $lang['search'] ?>
                </button>
            </div>
            
            <!-- Advanced Filters Section -->
            <div class="filters-section">
                <h5 class="mb-3 d-flex align-items-center">
                    <i class="fas fa-filter me-2"></i><?= $lang['advanced_filters'] ?>
                    <button type="button" class="collapse-btn" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
                        <i class="fas fa-chevron-up collapse-icon"></i> <?= $lang['toggle'] ?>
                    </button>
                    <a href="search.php" class="btn btn-sm btn-outline-secondary clear-filters">
                        <i class="fas fa-times me-1"></i> <?= $lang['clear_filters'] ?>
                    </a>
                </h5>
                
                <div class="collapse show" id="advancedFilters">
                    <div class="row">
                        <!-- Column 1 -->
                        <div class="col-md-4">
                            <div class="filter-group">
                                <div class="filter-label"><?= $lang['material_type'] ?></div>
                                <select name="type" class="form-select">
                                    <option value=""><?= $lang['all_types'] ?></option>
                                    <?php foreach ($types as $type): ?>
                                        <option value="<?= $type ?>" <?= $type_filter === $type ? 'selected' : '' ?>>
                                            <?= $type ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <div class="filter-label"><?= $lang['publisher'] ?></div>
                                <select name="publisher" class="form-select">
                                    <option value=""><?= $lang['all_publishers'] ?></option>
                                    <?php foreach ($publishers as $publisher): ?>
                                        <option value="<?= $publisher ?>" <?= $publisher_filter === $publisher ? 'selected' : '' ?>>
                                            <?= $publisher ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <div class="filter-label"><?= $lang['author'] ?></div>
                                <select name="author" class="form-select">
                                    <option value=""><?= $lang['all_authors'] ?></option>
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
                                <div class="filter-label"><?= $lang['isbn'] ?></div>
                                <input type="text" name="isbn" class="form-control" 
                                       placeholder="<?= $lang['enter_isbn'] ?>" 
                                       value="<?= htmlspecialchars($isbn_filter) ?>">
                            </div>
                            
                            <div class="filter-group">
                                <div class="filter-label"><?= $lang['issn'] ?></div>
                                <input type="text" name="issn" class="form-control" 
                                       placeholder="<?= $lang['enter_issn'] ?>" 
                                       value="<?= htmlspecialchars($issn_filter) ?>">
                            </div>
                            
                            <div class="filter-group">
                                <div class="filter-label"><?= $lang['category'] ?></div>
                                <select name="category" class="form-select">
                                    <option value=""><?= $lang['all_categories'] ?></option>
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
                                <div class="filter-label"><?= $lang['publication_year'] ?></div>
                                <select name="year" class="form-select">
                                    <option value=""><?= $lang['all_years'] ?></option>
                                    <?php foreach ($years as $year): ?>
                                        <option value="<?= $year ?>" <?= $year_filter == $year ? 'selected' : '' ?>>
                                            <?= $year ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <div class="filter-label"><?= $lang['language'] ?></div>
                                <select name="language" class="form-select">
                                    <option value=""><?= $lang['all_languages'] ?></option>
                                    <?php foreach ($languages as $language): ?>
                                        <option value="<?= $language ?>" <?= $language_filter === $language ? 'selected' : '' ?>>
                                            <?= $language ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <div class="filter-label"><?= $lang['added_by'] ?></div>
                                <select name="added_by" class="form-select">
                                    <option value=""><?= $lang['all_users'] ?></option>
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
                                <div class="filter-label"><?= $lang['status'] ?></div>
                                <select name="status" class="form-select">
                                    <option value=""><?= $lang['all_statuses'] ?></option>
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
                                <div class="filter-label"><?= $lang['sort_by'] ?></div>
                                <select name="sort" class="form-select">
                                    <option value="title" <?= $sort_by === 'title' ? 'selected' : '' ?>><?= $lang['title_asc'] ?></option>
                                    <option value="title_desc" <?= $sort_by === 'title_desc' ? 'selected' : '' ?>><?= $lang['title_desc'] ?></option>
                                    <option value="year" <?= $sort_by === 'year' ? 'selected' : '' ?>><?= $lang['year_asc'] ?></option>
                                    <option value="year_desc" <?= $sort_by === 'year_desc' ? 'selected' : '' ?>><?= $lang['year_desc'] ?></option>
                                    <option value="added" <?= $sort_by === 'added' ? 'selected' : '' ?>><?= $lang['added_asc'] ?></option>
                                    <option value="added_desc" <?= $sort_by === 'added_desc' ? 'selected' : '' ?>><?= $lang['added_desc'] ?></option>
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
                    <?= $totalRecords ?> <?= $lang['materials_found'] ?>
                    <small class="text-muted ms-2"><?= $search_query ? "{$lang['search']}: \"$search_query\"" : $lang['all_materials'] ?></small>
                </div>
                <div>
                    <?= $lang['showing'] ?> <?= min($recordsPerPage, count($materials)) ?> <?= $lang['of'] ?> <?= $totalRecords ?> <?= $lang['items'] ?>
                </div>
            </div>
            
            <?php if (empty($materials)): ?>
                <div class="alert alert-info text-center py-4">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h4><?= $lang['no_materials_found'] ?></h4>
                    <p class="mb-0"><?= $lang['adjust_search_criteria'] ?></p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <div class="table-responsive">
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th><?= $lang['id'] ?></th>
                                    <th><?= $lang['title'] ?></th>
                                    <th><?= $lang['authors'] ?></th>
                                    <th><?= $lang['type'] ?></th>
                                    <th><?= $lang['publisher'] ?></th>
                                    <th><?= $lang['year'] ?></th>
                                    <th><?= $lang['status'] ?></th>
                                    <th><?= $lang['actions'] ?></th>
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
                                        <td><?= $item['authors'] ? htmlspecialchars(substr($item['authors'], 0, 30)) . (strlen($item['authors']) > 30 ? '...' : '') : $lang['na'] ?></td>
                                        <td>
                                            <span class="badge badge-type">
                                                <i class="fas fa-book material-icon <?= $icon_class ?>"></i>
                                                <?= htmlspecialchars($item['type_name']) ?>
                                            </span>
                                        </td>
                                        <td><?= $item['publisher_name'] ? htmlspecialchars(substr($item['publisher_name'], 0, 15)) . (strlen($item['publisher_name']) > 15 ? '...' : '') : $lang['na'] ?></td>
                                        <td><?= $item['publication_year'] ? htmlspecialchars($item['publication_year']) : $lang['na'] ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $item['status'] ?>">
                                                <?= ucfirst($item['status']) ?>
                                            </span>





<?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) : ?>
    <td class="action-cell">
        <!-- Edit icon -->
        <a href="edit.php?id=<?= $item['item_id'] ?>" class="action-btn btn-edit" title="<?= $lang['edit'] ?>" onclick="event.stopPropagation()">
            <i class="fas fa-edit"></i>
        </a>
        
        <!-- Delete Icon -->
        <a href="delete.php?id=<?= $item['item_id'] ?>" class="action-btn btn-delete" title="<?= $lang['delete'] ?>" 
           onclick="return confirm('<?= $lang['confirm_delete_item'] ?>'); event.stopPropagation()">
            <i class="fas fa-trash"></i>
        </a>
    </td>
<?php endif; ?>
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