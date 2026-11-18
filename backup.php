<!DOCTYPE html>
<html lang="en">
<?php 
include_once 'includes/head.php'; 
?>
<body class="horizontal light">
  <div class="wrapper">
    <?php include_once 'includes/header.php'; ?>
    <main role="main" class="main-content">
      <div class="container-fluid">
        
        <!-- Page Title Header -->
        <div class="card shadow mb-4">
          <div class="card-header card-bg text-center">
            <h4 class="mb-0 text-white font-weight-bold">Database Backup & Restore Manager</h4>
          </div>
          <div class="card-body">
            
            <?php 
            // Load and process backup/restore logic
            include_once 'includes/backup_code.php'; 

            // Load settings
            $settings_file = file_exists(__DIR__ . '/settings.txt') ? __DIR__ . '/settings.txt' : 'settings.txt';
            $settings = file_exists($settings_file) ? explode('|', trim(file_get_contents($settings_file))) : [];
            
            $db_host = !empty($settings[0]) ? $settings[0] : ($localhost ?? 'localhost');
            $db_user = isset($settings[1]) && $settings[1] !== '' ? $settings[1] : ($username ?? 'root');
            $db_pass = isset($settings[2]) ? $settings[2] : ($password ?? '');
            $db_name = !empty($settings[3]) ? $settings[3] : ($dbname ?? 'chairman');
            $backup_dir = !empty($settings[4]) ? rtrim($settings[4], '/\\') : 'backup';

            // Ensure backup directory exists
            if (!is_dir($backup_dir)) {
                @mkdir($backup_dir, 0777, true);
            }

            // Handle file upload
            if (isset($_POST['upload_back_file']) && isset($_FILES['f'])) {
                $uploaded = $_FILES['f'];
                $ext = strtolower(pathinfo($uploaded['name'], PATHINFO_EXTENSION));
                $allowed = ['sql', 'gz'];

                if (!in_array($ext, $allowed)) {
                    echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                            <strong>Error:</strong> Only .sql and .gz files are permitted for upload.
                            <button type='button' class='close' data-dismiss='alert'><span>&times;</span></button>
                          </div>";
                } elseif ($uploaded['error'] !== UPLOAD_ERR_OK) {
                    echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                            <strong>Error:</strong> Upload failed with error code: " . (int)$uploaded['error'] . "
                            <button type='button' class='close' data-dismiss='alert'><span>&times;</span></button>
                          </div>";
                } else {
                    $clean_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($uploaded['name']));
                    $dest = $backup_dir . '/' . $clean_name;
                    if (move_uploaded_file($uploaded['tmp_name'], $dest)) {
                        echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                                <strong>Success!</strong> Backup file <b>" . htmlspecialchars($clean_name) . "</b> uploaded successfully. You can now restore it below.
                                <button type='button' class='close' data-dismiss='alert'><span>&times;</span></button>
                              </div>";
                    } else {
                        echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                                <strong>Error:</strong> Failed to save uploaded file. Check write permissions for folder: " . htmlspecialchars($backup_dir) . "
                                <button type='button' class='close' data-dismiss='alert'><span>&times;</span></button>
                              </div>";
                    }
                }
            }

            // Handle file deletion
            if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['backupfile'])) {
                $file_to_del = basename($_GET['backupfile']);
                $file_path = $backup_dir . '/' . $file_to_del;
                if (file_exists($file_path) && is_file($file_path)) {
                    if (@unlink($file_path)) {
                        echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                                <strong>Deleted:</strong> Backup file <b>" . htmlspecialchars($file_to_del) . "</b> has been removed.
                                <button type='button' class='close' data-dismiss='alert'><span>&times;</span></button>
                              </div>";
                    } else {
                        echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                                <strong>Error:</strong> Failed to delete file. Check server permissions.
                                <button type='button' class='close' data-dismiss='alert'><span>&times;</span></button>
                              </div>";
                    }
                }
            }
            ?>

            <!-- Top Action Cards -->
            <div class="row my-4">
              
              <!-- Card 1: Create Backup -->
              <div class="col-md-4 mb-3">
                <div class="card h-100 border-primary text-center shadow-sm">
                  <div class="card-body d-flex flex-column justify-content-between p-4">
                    <div>
                      <div class="fe fe-database fe-32 text-primary mb-2"></div>
                      <h5 class="card-title font-weight-bold">Create Database Backup</h5>
                      <p class="text-muted small">Generate a fresh full dump of the <b><?=htmlspecialchars($db_name)?></b> database.</p>
                    </div>
                    <form action="" method="post" class="mt-3">
                      <button type="submit" name="action" value="backup" class="btn btn-primary btn-block py-2 font-weight-bold">
                        <i class="fe fe-download-cloud mr-1"></i> Backup Database Now
                      </button>
                    </form>
                  </div>
                </div>
              </div>

              <!-- Card 2: Upload Backup File -->
              <div class="col-md-4 mb-3">
                <div class="card h-100 border-success text-center shadow-sm">
                  <div class="card-body d-flex flex-column justify-content-between p-4">
                    <div>
                      <div class="fe fe-upload-cloud fe-32 text-success mb-2"></div>
                      <h5 class="card-title font-weight-bold">Upload Backup File</h5>
                      <p class="text-muted small">Upload an external <code>.sql</code> or <code>.sql.gz</code> file to restore from.</p>
                    </div>
                    <form action="" method="post" enctype="multipart/form-data" class="mt-3">
                      <div class="custom-file mb-2 text-left">
                        <input type="file" name="f" class="custom-file-input" id="backupFile" accept=".sql,.gz" required onchange="this.nextElementSibling.innerText = this.files[0] ? this.files[0].name : 'Choose .sql file'">
                        <label class="custom-file-label text-truncate" for="backupFile">Choose .sql file</label>
                      </div>
                      <button type="submit" name="upload_back_file" class="btn btn-success btn-block py-2 font-weight-bold">
                        <i class="fe fe-upload mr-1"></i> Upload File
                      </button>
                    </form>
                  </div>
                </div>
              </div>

              <!-- Card 3: Server & Database Settings -->
              <div class="col-md-4 mb-3">
                <div class="card h-100 border-info text-center shadow-sm">
                  <div class="card-body d-flex flex-column justify-content-between p-4">
                    <div>
                      <div class="fe fe-settings fe-32 text-info mb-2"></div>
                      <h5 class="card-title font-weight-bold">Server & DB Configuration</h5>
                      <p class="text-muted small">Active Host: <b><?=htmlspecialchars($db_host)?></b> | DB: <b><?=htmlspecialchars($db_name)?></b></p>
                    </div>
                    <div class="mt-3">
                      <button type="button" class="btn btn-info btn-block py-2 font-weight-bold" data-toggle="modal" data-target="#settingsModal">
                        <i class="fe fe-sliders mr-1"></i> Configure Settings
                      </button>
                    </div>
                  </div>
                </div>
              </div>

            </div> <!-- .row -->

            <!-- Existing Backups Table Section -->
            <div class="card shadow-sm mt-4">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 font-weight-bold text-secondary">
                  <i class="fe fe-archive mr-2"></i> Existing Backup Files in <code>/<?=htmlspecialchars($backup_dir)?></code>
                </h5>
                <span class="badge badge-primary px-3 py-2">
                  <?php 
                  $all_files = is_dir($backup_dir) ? array_diff(scandir($backup_dir), ['.', '..']) : [];
                  echo count($all_files) . " File(s)";
                  ?>
                </span>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                      <tr>
                        <th style="width: 50px;">#</th>
                        <th>File Name</th>
                        <th style="width: 140px;">Size</th>
                        <th style="width: 200px;">Date & Time</th>
                        <th style="width: 260px;" class="text-center">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php 
                      if (!empty($all_files)) {
                          // Sort newest first
                          usort($all_files, function($a, $b) use ($backup_dir) {
                              return filemtime($backup_dir . '/' . $b) - filemtime($backup_dir . '/' . $a);
                          });

                          $sr = 1;
                          foreach ($all_files as $file) {
                              $full_path = $backup_dir . '/' . $file;
                              if (!is_file($full_path)) continue;

                              $bytes = filesize($full_path);
                              if ($bytes >= 1048576) {
                                  $size_formatted = round($bytes / 1048576, 2) . ' MB';
                              } elseif ($bytes >= 1024) {
                                  $size_formatted = round($bytes / 1024, 2) . ' KB';
                              } else {
                                  $size_formatted = $bytes . ' B';
                              }

                              $date_formatted = date("d M, Y - h:i A", filemtime($full_path));
                              ?>
                              <tr>
                                <td><?=$sr++?></td>
                                <td>
                                  <i class="fe fe-file-text mr-1 text-primary"></i>
                                  <b><?=htmlspecialchars($file)?></b>
                                </td>
                                <td><span class="badge badge-light border"><?=$size_formatted?></span></td>
                                <td class="text-muted"><small><?=$date_formatted?></small></td>
                                <td class="text-center">
                                  <!-- Download -->
                                  <a href="<?=htmlspecialchars($full_path)?>" download class="btn btn-sm btn-outline-primary mr-1" title="Download">
                                    <i class="fe fe-download"></i> Download
                                  </a>

                                  <!-- Restore -->
                                  <a href="backup.php?action=restore&backupfile=<?=urlencode($file)?>" 
                                     class="btn btn-sm btn-outline-warning mr-1" 
                                     title="Restore this backup"
                                     onclick="return confirm('WARNING: Restoring will overwrite existing data in the database with the content of <?=htmlspecialchars(addslashes($file))?>. Are you sure you want to proceed?');">
                                    <i class="fe fe-refresh-cw"></i> Restore
                                  </a>

                                  <!-- Delete -->
                                  <a href="backup.php?action=delete&backupfile=<?=urlencode($file)?>" 
                                     class="btn btn-sm btn-outline-danger" 
                                     title="Delete file"
                                     onclick="return confirm('Are you sure you want to delete <?=htmlspecialchars(addslashes($file))?>?');">
                                    <i class="fe fe-trash-2"></i>
                                  </a>
                                </td>
                              </tr>
                              <?php
                          }
                      } else {
                          echo "<tr><td colspan='5' class='text-center py-4 text-muted'><i class='fe fe-alert-circle mr-1'></i> No backup files found in directory.</td></tr>";
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

          </div> <!-- .card-body -->
        </div> <!-- .card -->

      </div> <!-- .container-fluid -->
    </main> <!-- main -->
  </div> <!-- .wrapper -->

  <!-- Settings Modal -->
  <div class="modal fade" id="settingsModal" tabindex="-1" role="dialog" aria-labelledby="settingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <form action="" method="post">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title font-weight-bold" id="settingsModalLabel">
              <i class="fe fe-sliders mr-2"></i> Database & Backup Settings
            </h5>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p class="text-muted small">These settings configure the connection for database backup and restore operations on this server.</p>
            
            <div class="form-group">
              <label class="font-weight-bold">Database Server / Host:</label>
              <input type="text" name="server" class="form-control" value="<?=htmlspecialchars($db_host)?>" required placeholder="e.g. localhost">
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Database User:</label>
              <input type="text" name="user" class="form-control" value="<?=htmlspecialchars($db_user)?>" placeholder="e.g. root or cpanel_user">
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Database Password:</label>
              <input type="password" name="password" class="form-control" value="<?=htmlspecialchars($db_pass)?>" placeholder="Enter password (leave empty if none)">
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Database Name:</label>
              <input type="text" name="database" class="form-control" value="<?=htmlspecialchars($db_name)?>" required placeholder="e.g. chairman">
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Backup Directory Path:</label>
              <input type="text" name="directory" class="form-control" value="<?=htmlspecialchars($backup_dir)?>" required placeholder="e.g. backup">
              <small class="form-text text-muted">Folder relative to project root where backups are saved.</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="submit" name="update_settings" value="1" class="btn btn-info font-weight-bold">
              <i class="fe fe-check mr-1"></i> Save Settings
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

</body>
</html>
<?php include_once 'includes/foot.php'; ?>
