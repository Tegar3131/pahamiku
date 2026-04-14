<style>
.global-footer {
    text-align: center;
    font-size: 13px;
    color: #9CA3AF;
    padding: 24px 20px;
    margin-top: 40px; 
    font-family: 'Nunito', sans-serif;
    border-top: 1px solid #E5E7EB;
    background-color: transparent;
    width: 100%;
}
.global-footer a {
    color: #2BB5AC;
    text-decoration: none;
    font-weight: 700;
}
.global-footer a:hover {
    text-decoration: underline;
}
</style>

<footer class="global-footer">
    <div style="max-width: 1000px; margin: 0 auto;">
        PAHAMIKU &copy; <?= date('Y') ?> &nbsp;&middot;&nbsp; 
        Simbol AAC oleh <a href="https://arasaac.org" target="_blank">ARASAAC</a> (CC BY-NC-SA) &nbsp;&middot;&nbsp;
        <a href="<?= BASE_URL ?>tentang.php">Tentang PAHAMIKU</a>
    </div>
</footer>
