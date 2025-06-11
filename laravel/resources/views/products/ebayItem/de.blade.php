
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.98.0/css/materialize.min.css">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/all.css" crossorigin="anonymous">
<style>
    h1 {
        text-align: center;
        color: #353535;
        font-family: Arial;
    }

    .description {
        text-align: center;
    }

    .container {
        padding: 40px;
    }

    .line {
        padding: 40px 0 0 0;
        border-bottom: 1px solid #CBD4C2;
    }

    .flex-center {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .flex-center-row {
        display: flex;
        flex-direction: row;
        align-items: center;
    }

    .reviews img {
        max-width: 100%;
    }

    #returns ul li {
        list-style: disc;
    }

    .mobile {
        display: none;
    }

    @media only screen and (max-width: 560px) {
        .mobile {
            display: block;
        }

        .tablet-pc {
            display: none;
        }
    }
</style>
<style>
    .scrollV > tbody {
        height: 250px;
        overflow: auto;
        display: block;
    }

    .scrollV > thead > tr, .scrollV tbody > tr {
        display: table;
        width: 100%;
        table-layout: fixed;
        /* even columns width , fix width of table too*/
    }

    body {
        font: 16px/1.5em "Overpass", "Open Sans", Helvetica, sans-serif;
        color: #333;
        font-weight: 300;
    }

    input:checked+label, label:hover {
        background-color: rgba(0,0,0,0.02)
    }

    input:checked+label {
        color: #fe6150;
        margin: 0px 0 4px 0;
    }

    input,label {
        background-color: #fff1f0
    }

    table.striped > tbody > tr:nth-child(odd) {
        background-color: #fff1f0;
    }

    .table-sm th, .table-sm td {
        font-size: 13px;
        padding: 4px;
    }

    .tabset .tab-panel {
        display: none;
    }

    .tabset > input[type="radio"]:checked + label::after {
        background-color: #fe6150;
    }

    .tabset > input[type="radio"] + label::before {
    }

    .tabset > input:first-child:checked ~ .tab-panels > .tab-panel:first-child, .tabset > input:nth-child(3):checked ~ .tab-panels > .tab-panel:nth-child(2), .tabset > input:nth-child(5):checked ~ .tab-panels > .tab-panel:nth-child(3), .tabset > input:nth-child(7):checked ~ .tab-panels > .tab-panel:nth-child(4), .tabset > input:nth-child(9):checked ~ .tab-panels > .tab-panel:nth-child(5), .tabset > input:nth-child(11):checked ~ .tab-panels > .tab-panel:nth-child(6) {
        display: block;
    }

    .tabset > input[type="radio"] {
        display: none;
    }

    .tabset > label {
        height: 40px !important;
        line-height: 40px !important;
        position: relative;
        padding: 0 3em !important;
        margin: unset;
        display: inline-flex !important;
        align-items: center;
    }

    .tabset > label::before, .tabset > label::after {
        border: none !important;
        border-radius: 0 !important;
        margin: unset !important;
        line-height: inherit !important;
    }

    .tabset > label::before {
        font-weight: 900;
        font-family: "Font Awesome 5 Free";
        -moz-osx-font-smoothing: grayscale;
        -webkit-font-smoothing: antialiased;
        display: inline-block;
        font-style: normal;
        font-variant: normal;
        text-rendering: auto;
        padding: 0 1rem;
    }

    /*icon color*/
    .tabset > input:checked + label::before {
        color: #fe6150;
    }

    .tabset > input[aria-controls="tech"] + label::before {
        content: "\f0ad";
    }

    .tabset > input[aria-controls="shipp"] + label::before {
        content: "\f0d1";
    }

    .tabset > input[aria-controls="returns"] + label::before {
        content: "\f0e2";
    }

    .tabset > input[aria-controls="feedback"] + label::before {
        content: "\f086";
    }

    .tabset > input[aria-controls="contacts"] + label::before {
        content: "\f0e0";
    }

    .tabset > label::after {
        content: '';
        position: absolute;
        border: 0;
        width: 100%;
        height: 2px;
        top: 100%;
        left: 0;
        background-color: #d7d7d7;
        transition: all .3s;
    }

    .tab-panels {
        border-top: 2px solid #d7d7d7;
    }

    .tab-panels .card {
        border-radius: 0 !important;
    }

    .tabset {
        max-width: 100%;
        flex-direction: column;
        overflow: hidden;
    }

    @media only screen and (min-width: 768px) {
        .tabset > input:checked + label::after {
            transform: scaleX(1);
        }

        .tabset > input + label::after {
            transform: scaleX(0);
        }
    }

    .fa-instagram {
        color: transparent;
        background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);
        background: -webkit-radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);
        background-clip: text;
        -webkit-background-clip: text;
    }

    @media only screen and (max-width: 768px) {
        .tabset > label {
            display: flex !important;
        }

        .tabset > input:checked + label {
            background-color: rgba(0, 0, 0, 0.03);
        }

        .tabset > input:checked + label::after {
            transform: scaleY(1);
        }

        .tab-panels {
            border-top: 2px solid #d7d7d7;
        }

        .tabset > label::after {
            top: 0;
            left: 0;
            height: 100%;
            width: 2px;
            transform: scaleY(0);
        }

        .vehicle-section {
            height: 450px;
            overflow: scroll;
        }
    }
</style>

<div class="container" style="padding: 0 40px">
    <div class="tabset" style="margin: 10px">
        <input type="radio" name="tabset" id="tab1" aria-controls="tech" checked="">
        <label for="tab1">Technische Details</label>
        <input type="radio" name="tabset" id="tab2" aria-controls="shipp">
        <label for="tab2">Versandinformationen</label>
        <input type="radio" name="tabset" id="tab3" aria-controls="returns">
        <label for="tab3">Rückgabe</label>
        <input type="radio" name="tabset" id="tab4" aria-controls="feedback">
        <label for="tab4">Bewertungen</label>
        <input type="radio" name="tabset" id="tab5" aria-controls="contacts">
        <label for="tab5">Kontakt</label>
        <div class="tab-panels" style="padding-top:35px">
            <section id="tech" class="tab-panel" style="text-align: center;">
                <div class="flex-center">
                    <img alt="" class="mx-auto d-block img-fluid" src="https://cortexparts.github.io/photo/other/logo.png" style="width: 200px;">
                    <h1 class="tablet-pc">Geprüfte&nbsp;Ersatzteile. Bewährte&nbsp;Marken.</h1>
                    <h1 class="mobile">Geprüfte Ersatzteile. Bewährte Marken.</h1>
                </div>
                <div class="description">
                    CortexParts – ein Autoteilegeschäft mit einer großen Auswahl, das sich auf hochwertige und geprüfte Teile spezialisiert hat, die immer auf Lager sind, für eine breite Palette von Fahrzeugen.
                </div>

                <div class="line"></div>
                @if($product and $product['ebay_name_de'])
                    <p class="card-title">
                      <b>
                        <em>{{ $product['ebay_name_de'] }}</em>
                      </b>
                    </p>
                @endif
                <div class="row" style="padding-top:25px">
                    @if($product and $product['photo'])
                        <div class="col s12 m4">
                            <div class="card">
                                <div class="card-image">
                                    <img class="responsive-img" style="" src="{{ $product['photo'] }}">
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($product and $product['specifics_de'])
                        <div class="col s12 m8">
                            <div class="card-panel">
                                <p class="header" style="color: #fe6150; font-size: 20px">
                                    <b>Technische Details</b>
                                </p>
                                <div class="card-content">
                                    <table class="table striped bordered table-sm ">
                                        <tbody>
                                            @foreach($product['specifics_de'] as $specific)
                                                <tr>
                                                    <td>{{ $specific['name'] }}</td>
                                                    <td>{{ $specific['value'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="row" style="padding-top:15px">
                    <div class="col s12 m12">
                        <div class="card-panel" style="padding:10px;">
                            <p class="header" style="color: #fe6150; font-size: 20px">
                                <b>HINWEIS</b>
                            </p>
                            <div class="card-content">
                                <p>Alle technischen Daten in der Artikelbeschreibung stammen aus den offiziellen technischen Datenbanken der Hersteller. Wenn Sie sich nicht sicher sind, ob das Teil zu Ihrem Fahrzeug passt, senden Sie uns bitte die FIN (Fahrzeug-Identifikationsnummer) Ihres Fahrzeugs – wir prüfen die Kompatibilität gerne für Sie. Bei Fragen kontaktieren Sie uns bitte!</p>
                            </div>
                        </div>
                        <!--  <div class="card-panel" style="padding:10px;">
                           <p class="header" style="color: #fe6150; font-size: 20px">
                             <b>Dear customers!</b>
                           </p>
                           <div class="card-content">
                             <p>Kindly remind you, there might be additional customs, duties, taxes or brokerage fees in your country. According to eBay rules we, as a seller, do not cover any import/customs fees. Local governments are outside of the seller's control and responsibility.</p>
                           </div>
                         </div> -->
                    </div>
                </div>
                <div style="text-align: left;">
                    <h3 style="text-align: center;">Über uns</h3>

                    <div class="feature">
                        <h5>🎯 Unser Kundenversprechen</h5>
                        <p>Bei uns steht der Kunde im Fokus – Ihre Zufriedenheit nach dem Kauf ist unser oberstes Anliegen. In unserem Shop finden Sie nicht nur eine große Auswahl an Ersatzteilen zu fairen Preisen, sondern auch kompetente Beratung und Unterstützung beim Kauf. Wenn Sie ein benötigtes Teil gefunden haben, können Sie es direkt bestellen. Teilen Sie uns einfach die FIN (Fahrzeug-Identifikationsnummer) mit – wir prüfen gerne, ob das Teil zu Ihrem Fahrzeug passt.</p>
                    </div>

                    <div class="feature">
                        <h5>🚚 Versand</h5>
                        <p>Vorrätige Artikel werden schnellstmöglich verschickt. Die Lieferzeit beträgt in der Regel <span class="highlight">2–5 Werktage</span>. Expressversand ist auf Wunsch möglich. Falls Sie eine besonders schnelle Lieferung benötigen, schreiben Sie uns – wir prüfen dann die besten Optionen für Ihre Adresse. Der Versand erfolgt über unsere zuverlässigen Partner: UPS, TNT, DHL, DPD, GLS.</p>
                    </div>

                    <div class="feature">
                        <h5>🛡️ Garantie</h5>
                        <p>Alle Artikel verfügen über eine <span class="highlight">Herstellergarantie</span>, die je nach Produkt zwischen 6 Monaten und 2 Jahren liegt. Wenn Sie Informationen zur Garantie Ihres Produkts benötigen, kontaktieren Sie uns bitte direkt.</p>
                        <p>Sie erhalten eine Rechnung mit ausgewiesener Mehrwertsteuer.</p>
                    </div>

                    <h5>📞 Kontakt</h5>
                    <div class="info-section">
                        <p>Bei Fragen oder Anliegen können Sie uns jederzeit über das <strong>eBay-Nachrichtensystem</strong> kontaktieren.
                            <br>Wir bemühen uns, Ihre Nachricht schnellstmöglich zu beantworten:<br>
                            <b>Antwortzeit: innerhalb von 24 Stunden an Werktagen.</b><br>
                            Unsere Geschäftszeiten: Montag bis Freitag, 7:00 – 17:00 Uhr (Berliner Zeit).<br><br>
                            Zögern Sie nicht, uns bei Fragen zu kontaktieren – wir helfen Ihnen gerne weiter!<br>
                            Ihre Zufriedenheit liegt uns am Herzen!</p>
                    </div>

                    <h5>💳 Zahlung</h5>
                    <div class="info-section">
                        <p>Die Bezahlung erfolgt sicher und bequem über eBay. Ihre Daten sind durch das eBay-Zahlungssystem geschützt.</p>
                    </div>

                    <h5>📦 Lieferung</h5>
                    <div class="info-section">
                        <p>Der Versand erfolgt innerhalb von <b>1–2 Werktagen</b> nach Zahlungseingang (Montag–Freitag). Wir bieten folgende Versandarten an:</p>
                        <ul>
                            <li><strong>Expressversand:</strong> 2–3 Werktage</li>
                            <li><strong>Standardversand:</strong> 2–10 Werktage</li>
                        </ul>
                    </div>

                    <h5>↩️ Rückgabe</h5>
                    <div class="info-section">
                        <p>Sie können Artikel, die Sie nicht benötigen und noch nicht eingebaut wurden, problemlos und ohne Angabe von Gründen zurücksenden.</p>
                    </div>
                    <div class="line"></div>

                    <h3 style="text-align: center;">Kundenbewertungen über uns</h3>
                    <div class="reviews" style="flex-center-row">
                        <img alt="" class="mx-auto d-block img-fluid" src="https://cortexparts.github.io/photo/other/reviews.png" style="/*width: 200px;">
                    </div>

                    <div class="line"></div>

                    <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script> CortexParts. All rights reserved.</p>
                </div>
            </section>
            <section id="shipp" class="tab-panel">
                <div class="row">
                    <div class="col s12">
                        <div class="card">
                            <div class="card-content" style="font-size: 16px;">
                                <span class="card-title" style="color: #fe6150;">📦 Versandinformationen</span>
                                <p style="margin-top:10px;">Wir versenden alle Bestellungen innerhalb von <b>1–2 Werktagen</b> nach Zahlungseingang (Montag bis Freitag).</p>

                                <p style="margin-top:10px;">
                                    <b>Expressversand</b> ist für ausgewählte Artikel verfügbar. Wenn Sie Ihre Bestellung so schnell wie möglich erhalten möchten, kontaktieren Sie uns – wir prüfen dann die verfügbaren Versandoptionen für Ihre Adresse. Der Versand erfolgt über unsere Partner: UPS, TNT, DHL, DPD, GLS.
                                </p>

                                <p>Wir bieten folgende Versandarten an:</p>

                                <div class="card-panel">
                                    <p class="header" style="color: #fe6150; font-size: 20px">
                                        <b>Versandarten & Laufzeiten</b>
                                    </p>
                                    <table class="table striped bordered table-sm">
                                        <tbody>
                                        <tr>
                                            <td><b>Economy Versand</b></td>
                                            <td>Lieferung innerhalb von 7–15 Werktagen in Europa und 12–31 Werktagen nach Amerika oder Australien.</td>
                                        </tr>
                                        <tr>
                                            <td><b>Standard Versand</b></td>
                                            <td>Lieferung mit Sendungsverfolgung. In Europa beträgt die Lieferzeit in der Regel 2–7 Werktage.</td>
                                        </tr>
                                        <tr>
                                            <td><b>Express Versand</b></td>
                                            <td>Lieferung mit DHL. Voraussichtliche Lieferzeit: 1–3 Werktage in Europa, 3–6 Werktage weltweit.</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <p style="margin:20px;"></p>

                                <center style="color: #fe6150;">
                                    <b>WICHTIG: BITTE GEBEN SIE BEI ALLEN BESTELLUNGEN EINE MOBILNUMMER AN.</b>
                                </center>

                                <p style="margin-top:10px;">Alle Sendungen werden per Kurier zugestellt und erfordern in der Regel eine Unterschrift. Bitte stellen Sie sicher, dass jemand vor Ort ist, um die Ware entgegenzunehmen. Falls das Paket an uns zurückgesendet wird, müssen wir die Kosten für den erneuten Versand berechnen.</p>

                                <p style="margin-top:10px;">Wir verwenden ein automatisiertes Bestellsystem. Bitte achten Sie darauf, dass während des eBay-/PayPal-Checkouts die korrekte Lieferadresse ausgewählt ist – wir versenden ausschließlich an die bei eBay hinterlegte Adresse (Richtlinie von eBay).</p>

                                <p style="margin-top:10px;">
                                    <b>Bei Stornierungen nach dem Versand</b> muss unser Rückgabeprozess befolgt werden. In diesem Fall tragen Sie die Versandkosten für Hin- und Rücksendung.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="line"></div>

                <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script> CortexParts. All rights reserved.</p>
            </section>
            <section id="returns" class="tab-panel">
                <div class="row">
                    <div class="col s12">
                        <div class="card">
                            <div class="card-content" style="font-size: 16px;">
                                <span class="card-title" style="color: #fe6150;">↩️ Rückgabe </span>
                                <ul>
                                    <li>Alle Rücksendungen müssen vor dem Versand über das eBay-Nachrichtensystem von uns genehmigt werden.</li>
                                    <li>Beschädigte Artikel müssen uns innerhalb von 24 Stunden nach Lieferung über das eBay-Nachrichtensystem mit einigen Fotos des Schadens melden.</li>
                                    <li>Wir akzeptieren Rücksendungen gerne innerhalb von 14 Tagen nach Lieferdatum.</li>
                                    <li>Wir übernehmen keine Rücksendekosten bei folgenden Gründen: passt nicht, entspricht nicht der Beschreibung oder den Fotos, funktioniert nicht oder ist defekt, wenn Sie vor dem Kauf nicht die Kompatibilität des Artikels mit der FIN (Fahrzeug-Identifikationsnummer) Ihres Fahrzeugs geprüft haben.</li>
                                    <li>Die Artikel müssen unbenutzt und im neuwertigen Zustand mit sämtlichem Originalzubehör (sofern enthalten) zurückgesendet werden.</li>
                                    <li>Alle zurückgesendeten Artikel werden vor der Rückerstattung geprüft.</li>
                                    <li>Versandkosten werden nicht erstattet, außer bei defekten Artikeln.</li>
                                    <li>Rücksendungen sollten in der Original-Versandverpackung verpackt sein, um Beschädigungen der Originalverpackung zu vermeiden.</li>
                                    <li>Beschädigte Rücksendungen werden nicht akzeptiert.</li>
                                    <li>Artikel, die vakuumverpackt oder mit Schmierfilm versiegelt sind, dürfen bei einer Rückgabe nicht geöffnet werden.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="line"></div>

                <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script> CortexParts. All rights reserved.</p>
            </section>
            <section id="feedback" class="tab-panel">
                <div class="row">
                    <div class="col s12">
                        <div class="card">
                            <div class="card-content" style="font-size: 16px;">
                                <span class="card-title" style="color: #fe6150;">🧑‍💼💬 Kundenbewertungen über uns</span>
                                <ul>
                                    <li>Bei uns steht der Kunde im Fokus – Ihre Zufriedenheit nach dem Kauf ist unser oberstes Anliegen.</li>
                                </ul>
                                <div class="reviews" style="flex-center-row">
                                    <img alt="" class="mx-auto d-block img-fluid" src="https://cortexparts.github.io/photo/other/reviews.png">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="line"></div>

                <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script> CortexParts. All rights reserved.</p>
            </section>
            <section id="contacts" class="tab-panel">
                <div class="row">
                    <div class="col s12">
                        <div class="card">
                            <div class="card-content " style="font-size: 16px;">
                                <span class="card-title" style="color: #fe6150;">📨 Kontakt</span>
                                Sie können uns jederzeit über <strong>eBay-Nachrichten</strong> kontaktieren, wenn Sie Fragen, Anregungen oder Produktwünsche haben.
                                <br>Wir bemühen uns, Ihre Anfragen schnellstmöglich zu beantworten:
                                <br><b>Alle Nachrichten werden innerhalb von 24 Stunden an Werktagen beantwortet.</b>
                                <br>Unsere regulären Geschäftszeiten sind Montag bis Freitag von 7:00 bis 17:00 Uhr Berliner Zeit.
                                <br><br>
                                Wir tun alles, um Ihnen bestmöglich zu helfen!
                                <br>Wir laden Sie herzlich ein, uns bei Fragen oder Anliegen jederzeit zu kontaktieren!
                                <br>Ihre vollständige Zufriedenheit mit Ihrem Kauf ist unser oberstes Ziel.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="line"></div>

                <p>Copyright &copy; <script>document.write(new Date().getFullYear())</script> CortexParts. All rights reserved.</p>
            </section>
        </div>
    </div>
</div>
