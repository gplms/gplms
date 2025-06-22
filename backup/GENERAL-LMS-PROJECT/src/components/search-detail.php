    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Material Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="book-cover">
                                <i class="fas fa-book"></i>
                            </div>
                            <h4 id="detailTitle" class="mb-3">Material Title</h4>
                            <div class="mb-4">
                                <span class="status-badge" id="detailStatus"></span>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="detail-item">
                                <span class="detail-label">Authors:</span>
                                <div class="detail-content" id="detailAuthors"></div>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Publisher:</span>
                                <span class="detail-content" id="detailPublisher"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Publication Year:</span>
                                <span class="detail-content" id="detailYear"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Language:</span>
                                <span class="detail-content" id="detailLanguage"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Edition:</span>
                                <span class="detail-content" id="detailEdition"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">ISBN:</span>
                                <span class="detail-content" id="detailISBN"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">ISSN:</span>
                                <span class="detail-content" id="detailISSN"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Category:</span>
                                <span class="detail-content" id="detailCategory"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Material Type:</span>
                                <span class="detail-content" id="detailType"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Added By:</span>
                                <span class="detail-content" id="detailAddedBy"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Added Date:</span>
                                <span class="detail-content" id="detailAddedDate"></span>
                            </div>
                            
                            <div class="mt-4">
                                <h5>Description:</h5>
                                <p id="detailDescription" class="card-text">No description available</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <div id="modalActions">
                        <!-- Actions will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
    </div>