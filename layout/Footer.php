    <footer class="footer-custom">
        <div class="container">
            <div class="row gy-3">

                <div class="col-md-4">
                    <p style="font-size:22px; font-weight:700; color:#ffffff; margin-bottom:6px;">
                        tri<span style="color:var(--trivago-blue);">vago</span>
                    </p>
                    <p class="mb-0">Find your ideal hotel deal.</p>
                </div>

                <div class="col-md-4">
                    <p class="mb-2 text-white fw-semibold">Company</p>
                    <a href="#" class="d-block mb-1">About trivago</a>
                    <a href="#" class="d-block mb-1">Careers</a>
                    <a href="#" class="d-block">Press</a>
                </div>

                <div class="col-md-4">
                    <p class="mb-2 text-white fw-semibold">Support</p>
                    <a href="#" class="d-block mb-1">Help Center</a>
                    <a href="#" class="d-block mb-1">Privacy Policy</a>
                    <a href="#" class="d-block">Terms &amp; Conditions</a>
                </div>

            </div>
            <hr style="border-color:#2e2e45; margin:24px 0;">
            <p class="text-center mb-0">© <?= date('Y') ?> trivago N.V. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- ══════════════════════════════════ -->
    <!--          PROFILE MODAL            -->
    <!-- ══════════════════════════════════ -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow">

                <div class="modal-header border-0">
                    <h5 class="modal-title">My Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-person-circle" style="font-size:60px; color:var(--trivago-blue);"></i>
                    </div>
                    <h5><?= htmlspecialchars($_SESSION['guest_name'] ?? 'User') ?></h5>
                    <p class="text-muted mb-1">
                        <?= htmlspecialchars($_SESSION['guest_email'] ?? '') ?>
                    </p>
                    <p class="text-muted mb-1">
                        Status:
                        <span class="badge <?= ($_SESSION['member_status'] ?? 'Guest') === 'Member' ? 'bg-primary' : 'bg-secondary' ?>">
                            <?= htmlspecialchars($_SESSION['member_status'] ?? 'Guest') ?>
                        </span>
                    </p>
                </div>

                <div class="modal-footer border-0 justify-content-center">
                    <button class="btn btn-trivago" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>

</body>
</html>