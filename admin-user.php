<?php
session_start();
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Focus - User Management</title>
    <link rel="icon" type="image/png" sizes="16x16" href="./images/favicon.png">
    <link href="./css/style.css" rel="stylesheet">
</head>

<body>
    <div id="preloader">
        <div class="sk-three-bounce">
            <div class="sk-child sk-bounce1"></div>
            <div class="sk-child sk-bounce2"></div>
            <div class="sk-child sk-bounce3"></div>
        </div>
    </div>

    <div id="main-wrapper">

        <div class="nav-header">
            <a href="index.html" class="brand-logo">
                <img class="logo-abbr" src="./images/logo.png" alt="">
                <img class="brand-title" src="./images/logo-text.png" alt="">
            </a>
            <div class="nav-control">
                <div class="hamburger"><span class="line"></span><span class="line"></span><span class="line"></span>
                </div>
            </div>
        </div>

        <div class="header">
            <div class="header-content">
                <nav class="navbar navbar-expand">
                    <div class="collapse navbar-collapse justify-content-between">
                        <div class="header-left"></div>
                        <ul class="navbar-nav header-right">
                            <li class="nav-item dropdown header-profile">
                                <a class="nav-link" href="#" role="button" data-toggle="dropdown">
                                    <i class="mdi mdi-account"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a href="./page-login.html" class="dropdown-item">
                                        <i class="icon-key"></i> <span class="ml-2">Logout</span>
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>
        <!--**********************************
            Sidebar start
        ***********************************-->
        <div class="quixnav">
            <div class="quixnav-scroll">
                <ul class="metismenu" id="menu">
                    <li class="nav-label first">เมนูหลัก</li>

                    <li>
                        <a href="admin_dashboard.php" aria-expanded="false">
                            <i class="icon icon-single-04"></i>
                            <span class="nav-text">ภาพรวม</span>
                        </a>
                    </li>

                    <li>
                        <a href="app-calender-admin.php" aria-expanded="false">
                            <i class="icon icon-app-store"></i> <span class="nav-text">ปฏิทิน</span>
                        </a>
                    </li>

                    <li>
                        <a href="admin-user.php" aria-expanded="false">
                            <i class="icon icon-users-mm-2"></i> <span class="nav-text">จัดการผู้ใช้</span>
                        </a>
                    </li>

                    <li class="nav-label">ข้อมูลการเกษตร</li>

                    <li>
                        <a href="production_admin.php" aria-expanded="false">
                            <i class="icon icon-layout-25"></i>
                            <span class="nav-text">จัดการข้อมูลเกษตร</span>
                        </a>
                    </li>

                    <li>
                        <a href="save-data.php" aria-expanded="false">
                            <i class="icon icon-form"></i>
                            <span class="nav-text">บันทึกการผลิต</span>
                        </a>
                    </li>

                </ul>
            </div>
        </div>
        <!--**********************************
            Sidebar end
        ***********************************-->
        <div class="content-body">
            <div class="container-fluid">
                <div class="row page-titles mx-0">
                    <div class="col-sm-6 p-md-0">
                        <div class="welcome-text">
                            <h4>จัดการข้อมูลผู้ใช้งาน</h4>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">รายชื่อผู้ใช้งานทั้งหมด</h4>
                                <button type="button" class="btn btn-primary" data-toggle="modal"
                                    data-target="#addUserModal">
                                    <i class="fa fa-plus"></i> เพิ่มผู้ใช้งาน
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-responsive-sm">
                                        <thead class="thead-primary">
                                            <tr>
                                                <th>#</th>
                                                <th>Username</th>
                                                <th>ชื่อ</th>
                                                <th>นามสกุล</th>
                                                <th>ระดับสิทธิ์</th>
                                                <th>สถานะ</th>
                                                <th>จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // ใช้ตาราง users (มี s)
                                            $sql = "SELECT * FROM users ORDER BY id DESC";
                                            $result = $conn->query($sql);

                                            if ($result && $result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    $roleBadge = ($row['role'] == 'admin') ? 'badge-primary' : 'badge-secondary';
                                                    $statusColor = ($row['status'] == 'active') ? 'text-success' : 'text-danger';
                                                    ?>
                                                    <tr>
                                                        <th><?php echo $row['id']; ?></th>
                                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['surname']); ?></td>
                                                        <td><span
                                                                class="badge <?php echo $roleBadge; ?>"><?php echo $row['role']; ?></span>
                                                        </td>
                                                        <td><span
                                                                class="<?php echo $statusColor; ?>"><?php echo $row['status']; ?></span>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-warning edit-btn"
                                                                data-toggle="modal" data-target="#editUserModal"
                                                                data-id="<?php echo $row['id']; ?>"
                                                                data-username="<?php echo $row['username']; ?>"
                                                                data-name="<?php echo $row['name']; ?>"
                                                                data-surname="<?php echo $row['surname']; ?>"
                                                                data-role="<?php echo $row['role']; ?>"
                                                                data-status="<?php echo $row['status']; ?>">
                                                                <i class="fa fa-pencil"></i>
                                                            </button>

                                                            <a href="javascript:void(0)"
                                                                onclick="confirmDelete(<?php echo $row['id']; ?>)"
                                                                class="btn btn-sm btn-danger">
                                                                <i class="fa fa-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                            } else {
                                                echo "<tr><td colspan='7' class='text-center'>ไม่พบข้อมูล หรือ ชื่อตารางไม่ถูกต้อง</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="copyright">
                <p>Copyright © Designed &amp; Developed by Quixkit 2019</p>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addUserModal">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มผู้ใช้งานใหม่</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <form action="db_add_user.php" method="POST">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>ชื่อ</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>นามสกุล</label>
                                    <input type="text" class="form-control" name="surname" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>ระดับสิทธิ์</label>
                                    <select class="form-control" name="role">
                                        <option value="user">User</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>สถานะ</label>
                                    <select class="form-control" name="status">
                                        <option value="active">Active (เปิดใช้งาน)</option>
                                        <option value="inactive">Inactive (ปิดใช้งาน)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-primary">บันทึก</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUserModal">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">แก้ไขข้อมูลผู้ใช้งาน</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <form action="db_edit_user.php" method="POST">
                        <input type="hidden" name="id" id="edit_id">

                        <div class="form-group">
                            <label>Username (แก้ไขไม่ได้)</label>
                            <input type="text" class="form-control" name="username" id="edit_username" readonly>
                        </div>
                        <div class="form-group">
                            <label>เปลี่ยนรหัสผ่าน (เว้นว่างถ้าไม่เปลี่ยน)</label>
                            <input type="password" class="form-control" name="password" placeholder="******">
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>ชื่อ</label>
                                    <input type="text" class="form-control" name="name" id="edit_name" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>นามสกุล</label>
                                    <input type="text" class="form-control" name="surname" id="edit_surname" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>ระดับสิทธิ์</label>
                                    <select class="form-control" name="role" id="edit_role">
                                        <option value="user">User</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>สถานะ</label>
                                    <select class="form-control" name="status" id="edit_status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-warning">อัปเดต</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="./vendor/global/global.min.js"></script>
    <script src="./js/quixnav-init.js"></script>
    <script src="./js/custom.min.js"></script>

    <script>
        $(document).ready(function () {
            $('.edit-btn').on('click', function () {
                $('#edit_id').val($(this).data('id'));
                $('#edit_username').val($(this).data('username'));
                $('#edit_name').val($(this).data('name'));
                $('#edit_surname').val($(this).data('surname'));
                $('#edit_role').val($(this).data('role'));
                $('#edit_status').val($(this).data('status'));
            });
        });

        function confirmDelete(id) {
            if (confirm("คุณต้องการลบข้อมูล ID: " + id + " ใช่หรือไม่?")) {
                window.location.href = 'db_delete_user.php?id=' + id;
            }
        }
    </script>
</body>

</html>