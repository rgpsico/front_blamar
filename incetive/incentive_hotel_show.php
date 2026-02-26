<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blumar - Incentive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/show.css">
        <link rel="stylesheet" href="css/show_modal.css">
    
    
</head>
<body>
<!-- Gallery Modal -->
<!-- Gallery Modal -->
<div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="galleryModalLabel">Incentive Gallery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 gallery-grid">
                    <!-- As imagens serão inseridas aqui via JS ou estaticamente -->
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

    <header class="top-bar">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="brand-logo">BLUMAR</div>
            <div class="d-flex align-items-center gap-3">
                <img src="https://flagcdn.com/24x18/us.png" alt="English">
                <a href="incentives_hotel.php" class="btn btn-sm btn-secondary" style="background-color: #5a7d91; border:none;">Back to Main Site</a>
            </div>
        </div>
    </header>

    <div class="page-header">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <span class="fw-bold fs-5">Incentives</span>
                <span class="ms-2 text-muted" id="hotelCitySubtitle"></span>
            </div>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle border-0" type="button" data-bs-toggle="dropdown">
                    Change the city
                </button>
            </div>
        </div>
    </div>

    <main class="container">
        
        <nav aria-label="breadcrumb" class="breadcrumb-area">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted">Incentive Area</a></li>
                <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted">Incentives</a></li>
                <li class="breadcrumb-item text-muted" id="hotelCity"></li>
                <li class="breadcrumb-item active" aria-current="page" id="hotelNameBreadcrumb"></li>
            </ol>
        </nav>

       <div class="row align-items-center">
            <div class="col d-flex justify-content-between align-items-center">
                <h1 class="hotel-main-title mb-3" id="hotelTitle"></h1>

                <button class="btn btn-proposal py-2 mb-3" style="width: 300px;">
                    <i class="far fa-edit me-2"></i>Create proposal
                </button>
            </div>
        </div>


        <div class="row hotel-content" id="hotel-content">
            <div class="col-lg-9">
                
                <div class="row g-2 mb-4">
                    <div class="col-md-9 gallery-main" id="hotelMainMedia">
                  <img src="img/hotel_01.png" alt="Hotel" style="width:100%; height:422px; object-fit:cover; border-radius:8px;">
                    </div>
                    <div class="col-md-3 gallery-thumbs">
                        <img id="thumb1" src="img/hotel_01.png" alt="Hotel">
                        <img id="thumb2" src="img/hotel_01.png" alt="Hotel">
                        
                        <div class="gallery-trigger position-relative" data-bs-toggle="modal" data-bs-target="#galleryModal">
                            <img id="thumb3" src="img/hotel_01.png" alt="Mais fotos" style="margin-bottom:0;">
                            <div class="gallery-more-overlay" id="galleryCount">
                                <i class="fas fa-camera me-2"></i> View 32 photos
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-separator"></div>
                <h3 class="section-title">Hotel Description</h3>
                <p class="text-muted small mb-4" id="hotelDescription"></p>

                <hr class="text-muted opacity-25">

                <style>
                    /* Room Categories Section */
.section-title {
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
}

.section-icon {
    font-size: 20px;
    color: #5a6c7d;
    margin-right: 10px;
}

.small.text-muted {
    font-size: 13px;
    color: #6c757d;
    line-height: 1.5;
}

/* Room Categories Lists */
.facility-list {
    list-style: none;
    padding-left: 0;
    margin-bottom: 0;
}

.facility-list li {
    font-size: 13px;
    color: #495057;
    line-height: 2;
    margin-bottom: 4px;  
    display: flex;
    align-items: center;
}

.facility-list li i.fa-check {
    color: #28a745;
    font-size: 12px;
    margin-right: 8px;
    min-width: 16px;
}

.facility-list li i.fa-circle {
    color: #6c757d;
    margin-right: 8px;
    min-width: 10px;
}

/* Right Column Styling */
.col-md-4 h6.small.fw-bold {
    font-size: 12px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 12px;
    text-transform: none;
}

/* Room category numbers in parentheses */
.facility-list li strong,
.small strong {
    font-weight: 600;
}

/* Overall container spacing */
.mt-6 {
    margin-top: 2rem;
}

.row.mt-3 {
    margin-top: 1rem !important;
}
                </style>
                <div class="mt-6">
                    <h3 class="section-title"><i class="fas fa-bed section-icon"></i> Room Categories</h3>
                    <p class="small text-muted" style="margin-left:40px;">Hotel has <strong id="totalRoomsCount">239 rooms</strong> and suites total.</p>
                    
                    <div class="row mt-3"  style="margin-left:20px;">
                        <div class="col-md-8">
                            <ul class="facility-list" id="roomCategoriesList">
                                <li><i class="fas fa-check"></i> Superior City View (30m²)</li>
                                <li><i class="fas fa-check"></i> Deluxe Beach View (40m²)</li>
                                <li><i class="fas fa-check"></i> Pool Ocean View Suite (60m²)</li>
                                <li><i class="fas fa-check"></i> Penthouse Suite (100m²)</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6 class="small fw-bold">the rom catgeories are</h6>
                            <ul class="facility-list" id="roomFacilitiesList">
                                <li><i class="fas fa-circle" style="font-size:6px; vertical-align:middle;"></i> Air Conditioning</li>
                                <li><i class="fas fa-circle" style="font-size:6px; vertical-align:middle;"></i> Mini Bar</li>
                                <li><i class="fas fa-circle" style="font-size:6px; vertical-align:middle;"></i> Safe deposit box</li>
                                <li><i class="fas fa-circle" style="font-size:6px; vertical-align:middle;"></i> Wi-Fi High Speed</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <hr class="text-muted opacity-25">

                <div class="mt-4">
                    <h3 class="section-title"><i class="fas fa-utensils section-icon"></i> Dining Experience</h3>
                    <div id="diningList">
                    <div class="dining-item">
                        <img src="https://images.unsplash.com/photo-1559339352-11d035aa65de?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80" class="dining-img">
                        <div>
                            <h5 class="fw-bold fs-6">Pérgula Restaurant</h5>
                            <p class="small text-muted">Located by the pool, Pérgula offers a relaxed atmosphere. It serves breakfast, lunch and dinner with a view of the legendary pool.</p>
                        </div>
                    </div>

                    <div class="dining-item">
                        <img src="https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80" class="dining-img">
                        <div>
                            <h5 class="fw-bold fs-6">Mee</h5>
                            <p class="small text-muted">Pan-Asian cuisine. The restaurant promises a journey through different Asian countries. Michelin Star awarded.</p>
                        </div>
                    </div>
                    </div>
                </div>

                <hr class="text-muted opacity-25">

                <div class="mt-4 mb-5">
                    <h5 class="fw-bold fs-6 mb-3">Hotel Facilities</h5>
                    <div class="row" id="hotelFacilities">
                        <div class="col-md-3">
                            <ul class="facility-list">
                                <li><i class="fas fa-check"></i> Pool</li>
                                <li><i class="fas fa-check"></i> Spa</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <ul class="facility-list">
                                <li><i class="fas fa-check"></i> Fitness Center</li>
                                <li><i class="fas fa-check"></i> Tennis Court</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <ul class="facility-list">
                                <li><i class="fas fa-check"></i> Beach Service</li>
                                <li><i class="fas fa-check"></i> Boutique</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="bg-light p-4 rounded mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                         <div class="section-separator m-0"></div>
                      
                    </div>
                    <h3 class="section-title mt-2">Convention Center and Event Facilities        <button class="btn btn-sm btn-outline-warning text-dark">View floor plan of the area</button></h3>
                    <p class="small text-muted mb-4" id="conventionDescription">The hotel offers luxurious and extensive convention areas, featuring 13 meeting rooms that can host a variety of events.</p>

                    <div class="table-responsive" class="table-facilities">
                        <table class="table table-bordered capacity-table table-hover bg-white">
                            <thead>
                                <tr>
                                    <th>Room Name</th>
                                    <th>Area (m²)</th>
                                    <th>Height (m)</th>
                                    <th>Theater</th>
                                    <th>School</th>
                                    <th>U-Shape</th>
                                    <th>Banquet</th>
                                    <th>Cocktail</th>
                                </tr>
                            </thead>
                            <tbody id="conventionRoomsBody">
                                <tr>
                                    <td>Golden Room</td>
                                    <td>300</td>
                                    <td>5.0</td>
                                    <td>500</td>
                                    <td>250</td>
                                    <td>80</td>
                                    <td>350</td>
                                    <td>600</td>
                                </tr>
                                <tr>
                                    <td>Nobre Room</td>
                                    <td>200</td>
                                    <td>4.5</td>
                                    <td>300</td>
                                    <td>150</td>
                                    <td>60</td>
                                    <td>200</td>
                                    <td>400</td>
                                </tr>
                                <tr>
                                    <td>Palm Room</td>
                                    <td>150</td>
                                    <td>3.5</td>
                                    <td>120</td>
                                    <td>80</td>
                                    <td>40</td>
                                    <td>100</td>
                                    <td>150</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <style>

                        hr {
                       
                        width: 90%;
                        margin: 20px auto;
                    }
                     
             .table-linha_planta {
                
                display: block;
                width: 100%;
                height: 4px;
                background: #d95c2b;
                margin-bottom: 20px;
            }


                    </style>
                    
                    <div class="text-center" style="">
                      <div class="container my-5">    
                        <div class="text-center mb-4">
                            <h4 class="fw-bold text-uppercase">Belmond Copacabana Palace</h4>
                            <span class="text-danger fw-semibold">Plantas & Medidas</span>
                            <hr class="mt-3" class="linha_planta">
                        </div>

                        <div class="text-center">
                            <img src="img/planta_hotel.png" id="conventionFloorPlan"
                                class="img-fluid border" 
                                alt="Floor Plan">
                        </div>
                        </div>
                    </div>
                </div>

            </div>
    
            <?php include 'sidebar.php'; ?>
        </div>
    </main>

    <script src="js/incentive_hotel_show.js"></script>
    <script src="js/sidebarshow.js"></script>
    <!-- Bootstrap JS + Popper (necessário para modais, dropdowns, etc) -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
       <?php include 'footer_show.php'; ?>
