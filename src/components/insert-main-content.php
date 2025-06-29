    <!-- Main Content -->
    <div class="container py-4">
        <div class="main-container">
            <h1 class="text-center mb-4"><i class="fas fa-plus-circle me-2"></i>Add New Materials</h1>
            
            <?php if ($success_message): ?>
                <div class="success-alert">
                    <i class="fas fa-check-circle me-2"></i> <?= $success_message ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="error-alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= $error_message ?>
                </div>
            <?php endif; ?>
            
            <!-- Toggle Section -->
            <div class="text-center mb-4">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-primary-bg active" id="formToggle">
                        <i class="fas fa-keyboard me-2"></i>Form Entry
                    </button>
                    <button type="button" class="btn btn-outline-bg" id="csvToggle">
                        <i class="fas fa-file-csv me-2"></i>CSV Upload
                    </button>
                </div>
            </div>
            
            <!-- Form Entry Section -->
            <div id="formSection">
                <form method="POST">
                    <input type="hidden" name="insert_type" value="manual">
                    
                    <div class="form-card">
                        <h3 class="section-title">Material Information</h3>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Material Type <span class="text-danger">*</span></label>
                                <select name="type_id" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($types as $type): ?>
                                        <option value="<?= $type['type_id'] ?>"><?= $type['type_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['category_id'] ?>"><?= $category['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Language <span class="text-danger">*</span></label>
                                <select name="language" class="form-select" required>
                                    <option value="EN">English</option>
                                    <option value="GR">Greek</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Publication Year</label>
                                <input type="number" name="publication_year" class="form-control" min="1000" max="<?= date('Y') ?>">
                            </div>
                        </div>
                        
                        <!-- NEW CATEGORY SECTION -->
                        <div class="toggle-section" onclick="toggleSection('newCategorySection')">
                            <h5 class="mb-0">
                                <i class="fas fa-plus me-2"></i>Add New Category
                                <i class="fas fa-chevron-down float-end"></i>
                            </h5>
                        </div>
                        
                        <div class="hidden-section mt-3" id="newCategorySection">
                            <div class="new-field">
                                <label class="form-label">Category Name</label>
                                <input type="text" name="new_category" class="form-control">
                            </div>
                        </div>
                        <!-- END NEW CATEGORY SECTION -->
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Edition</label>
                                <input type="number" name="edition" class="form-control" min="1">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 class="section-title">Identifiers</h3>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">ISBN <span class="identifier-badge">For Books</span></label>
                                <input type="text" name="isbn" class="form-control">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ISSN <span class="identifier-badge">For Periodicals</span></label>
                                <input type="text" name="issn" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 class="section-title">Publisher</h3>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Publisher</label>
                            <select name="publisher_id" class="form-select">
                                <option value="">Select Publisher</option>
                                <?php foreach ($publishers as $publisher): ?>
                                    <option value="<?= $publisher['publisher_id'] ?>"><?= $publisher['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="toggle-section" onclick="toggleSection('newPublisherSection')">
                            <h5 class="mb-0">
                                <i class="fas fa-plus me-2"></i>Add New Publisher
                                <i class="fas fa-chevron-down float-end"></i>
                            </h5>
                        </div>
                        
                        <div class="hidden-section mt-3" id="newPublisherSection">
                            <div class="new-field">
                                <label class="form-label">Publisher Name</label>
                                <input type="text" name="new_publisher" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 class="section-title">Authors</h3>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Authors</label>
                            <select name="author_ids[]" class="form-select" multiple size="5">
                                <?php foreach ($authors as $author): ?>
                                    <option value="<?= $author['author_id'] ?>"><?= $author['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple authors</small>
                        </div>
                        
                        <div class="toggle-section" onclick="toggleSection('newAuthorSection')">
                            <h5 class="mb-0">
                                <i class="fas fa-plus me-2"></i>Add New Authors
                                <i class="fas fa-chevron-down float-end"></i>
                            </h5>
                        </div>
                        
                        <div class="hidden-section mt-3" id="newAuthorSection">
                            <div class="new-field">
                                <label class="form-label">Author 1</label>
                                <input type="text" name="new_author[]" class="form-control">
                            </div>
                            
                            <div class="new-field">
                                <label class="form-label">Author 2</label>
                                <input type="text" name="new_author[]" class="form-control">
                            </div>
                            
                            <div class="new-field">
                                <label class="form-label">Author 3</label>
                                <input type="text" name="new_author[]" class="form-control">
                            </div>
                            
                            <button type="button" class="btn btn-outline-bg" onclick="addAuthorField()">
                                <i class="fas fa-plus me-2"></i>Add Another Author
                            </button>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary-bg btn-lg">
                            <i class="fas fa-save me-2"></i>Add Material
                        </button>
                    </div>
                </form>
            </div>
            
                      <!-- CSV Upload Section -->
            <div id="csvSection" class="hidden-section">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-card">
                        <h3 class="section-title">Upload CSV File</h3>
                        
                        <div class="csv-instructions">
                            <h5><i class="fas fa-info-circle me-2"></i>CSV Format Instructions</h5>
                            <p class="mb-0">Your CSV file should follow this format exactly. The first row should be headers.</p>
                        </div>
                        
                        <div class="table-responsive">
                        <!-- In the CSV Upload Section -->
<table class="csv-table">
    <thead>
        <tr>
            <th>Title</th>
            <th>Type Name</th>
            <th>Category Name</th>
            <th>Publisher Name</th>
            <th>Language</th>
            <th>Year</th>
            <th>Edition</th>
            <th>ISBN</th>
            <th>ISSN</th>
            <th>Description</th>
            <th>Authors (semicolon separated)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Introduction to Programming</td>
            <td>Book</td>
            <td>Computer Science</td>
            <td>Tech Publishers</td>
            <td>EN</td>
            <td>2022</td>
            <td>3</td>
            <td>1234567890123</td>
            <td></td>
            <td>Basic programming concepts</td>
            <td>John Smith;Jane Doe</td>
        </tr>
        <tr>
            <td>Science Monthly</td>
            <td>Magazine</td>
            <td>Science</td>
            <td>Science Press</td>
            <td>EN</td>
            <td>2023</td>
            <td></td>
            <td></td>
            <td>9876-543</td>
            <td>Monthly science magazine</td>
            <td>Editorial Team</td>
        </tr>
    </tbody>
</table>
                        </div>
                        
                        <div class="mt-4">
                            <label class="form-label">Select CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary-bg btn-lg">
                                <i class="fas fa-upload me-2"></i>Upload CSV
                            </button>
                            
                            <a href="#" class="btn btn-outline-bg btn-lg ms-2">
                                <i class="fas fa-download me-2"></i>Download Template
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>