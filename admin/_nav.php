<?php
$pendingJobs = (int) db_value("SELECT COUNT(*) FROM jobs WHERE status = 'pending'");
$newReports = (int) db_value("SELECT COUNT(*) FROM reports WHERE status = 'new'");
$newContacts = (int) db_value("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'");
$adminPage = basename($_SERVER['SCRIPT_NAME']);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 fw-bold mb-0"><i class="bi bi-shield-lock text-brand"></i> Admin</h1>
</div>
<ul class="nav nav-pills admin-nav mb-4 gap-1 flex-nowrap overflow-auto">
    <li class="nav-item"><a class="nav-link text-nowrap <?= $adminPage === 'index.php' ? 'active' : '' ?>" href="<?= e(url('admin/index.php')) ?>">Overview</a></li>
    <li class="nav-item"><a class="nav-link text-nowrap <?= $adminPage === 'jobs.php' ? 'active' : '' ?>" href="<?= e(url('admin/jobs.php')) ?>">Jobs <?php if ($pendingJobs): ?><span class="badge text-bg-warning"><?= $pendingJobs ?></span><?php endif; ?></a></li>
    <li class="nav-item"><a class="nav-link text-nowrap <?= $adminPage === 'users.php' ? 'active' : '' ?>" href="<?= e(url('admin/users.php')) ?>">Users</a></li>
    <li class="nav-item"><a class="nav-link text-nowrap <?= $adminPage === 'reports.php' ? 'active' : '' ?>" href="<?= e(url('admin/reports.php')) ?>">Reports <?php if ($newReports): ?><span class="badge text-bg-danger"><?= $newReports ?></span><?php endif; ?></a></li>
    <li class="nav-item"><a class="nav-link text-nowrap <?= $adminPage === 'inbox.php' ? 'active' : '' ?>" href="<?= e(url('admin/inbox.php')) ?>">Contact inbox <?php if ($newContacts): ?><span class="badge text-bg-info"><?= $newContacts ?></span><?php endif; ?></a></li>
</ul>
