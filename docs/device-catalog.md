# Supported Hardware Providers & Model Catalog (166+ Models)

This catalog details the **10 major global hardware brands** and **166+ officially supported models** integrated into `laravel-attendance-hub`.

Rather than writing fragmented, model-specific code for every terminal, `laravel-attendance-hub` normalizes them across core **protocol families**:
- **ZKTeco Protocol (TCP/UDP `:4370`)**: Native LAN socket communication (ZKTeco, FingerTec, Granding, Deli).
- **ADMS / iClock Cloud Push (`HTTP /iclock/cdata`)**: Firewall-free push protocol for WAN branch deployments (ZKTeco, eSSL, Anviz, Realtime).
- **Hikvision ISAPI (HTTP/HTTPS REST `:80/443`)**: XML/JSON access control and face recognition API.
- **Suprema BioStar 2 (HTTPS REST `:443`)**: Enterprise REST API for high-security biometric deployments.
- **Dahua NetSDK / CGI (HTTP `:80`)**: Access control event queries with digest authentication.
- **IoT / Wiegand Relay Bridge (`POST /api/attendance/webhook-bridge`)**: Token-authenticated JSON bridge for ESP32, Raspberry Pi, VIRDI, and Soyal panels.

---

## 📊 Summary by Brand

| Provider Brand | Models Cataloged | Primary Protocols | Native Driver | Typical Use Case |
|---|:---:|---|---|---|
| **1. ZKTeco** | **20** | TCP/UDP `:4370`, ADMS Push | `zkteco` / `adms` | Factories, corporate offices, visible light face |
| **2. Hikvision** | **15** | ISAPI REST (HTTP/HTTPS) | `hikvision` | Enterprise buildings, CCTV + access control |
| **3. Anviz** | **21** | CrossChex B-comm, ADMS | `adms` | Global offices, cloud-managed terminals |
| **4. FingerTec** | **18** | TCP/UDP `:4370`, USB | `zkteco` | Corporate time attendance, access control |
| **5. eSSL** | **22** | ADMS Push, TCP/UDP `:4370` | `adms` / `zkteco` | India, Bangladesh, South Asia enterprises |
| **6. VIRDI** | **12** | TCP/IP `:9870`, Wiegand, UNIS | `webhook_bridge` | High-security, outdoor IP65 rugged terminals |
| **7. Suprema** | **18** | BioStar 2 REST API (`:443`) | `suprema` | High-throughput enterprise & government |
| **8. Deli** | **10** | TCP/UDP `:4370`, USB | `zkteco` | Budget retail, small-to-medium business |
| **9. Granding** | **15** | TCP/UDP `:4370`, ADMS Push | `zkteco` | Hybrid biometric & wireless GPRS/Wi-Fi |
| **10. Soyal** | **15** | RS-485, TCP Converter, Wiegand | `webhook_bridge` | Turnstiles, elevators, industrial access control |
| **Total** | **166 Models** | | | |

---

## 1. ZKTeco (20 Models)

| Model | Series | FP | Face | Palm | RFID | Protocol / Port | ADMS | Driver | Difficulty | BD Market Availability | Notes |
|---|---|:---:|:---:|:---:|:---:|---|:---:|---|:---:|:---:|---|
| **UA860** | UA Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Very High | TCP/IP, Wi-Fi, USB, ADMS push built-in |
| **UA760** | UA Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Very High | Color screen fingerprint & RFID terminal |
| **UA660** | UA Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | BioID fingerprint sensor with ADMS |
| **UA300** | UA Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Very High | Widely deployed classic biometric terminal |
| **UA200** | UA Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ❌ | `zkteco` | Easy | High | Standard standalone TCP/IP fingerprint reader |
| **MB20** | MB Series | ✅ | ✅ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Very High | Multi-biometric fingerprint + visible face reader |
| **MB30** | MB Series | ✅ | ✅ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Hybrid biometric time attendance with ADMS |
| **MB40** | MB Series | ✅ | ✅ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Dual fingerprint and facial verification |
| **MB360** | MB Series | ✅ | ✅ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Very High | Enterprise hybrid face + fingerprint device |
| **MB560-VL** | Visible Light | ✅ | ✅ | ✅ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Visible Light face recognition with anti-spoofing |
| **MB460** | MB Series | ✅ | ✅ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Face + fingerprint + card reader |
| **K14** | K Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Very High | Ultra-popular budget model in South Asia |
| **K40** | K Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Very High | Fingerprint terminal with built-in battery backup |
| **K50** | K Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ❌ | `zkteco` | Easy | High | Color display biometric terminal with SSR reports |
| **K60** | K Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ❌ | `zkteco` | Easy | High | Standard network time attendance terminal |
| **iClock 260** | iClock Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `adms` | Easy | High | Enterprise ADMS cloud push fingerprint terminal |
| **iClock 360** | iClock Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `adms` | Easy | Very High | Heavy duty factory & corporate ADMS terminal |
| **iClock 700** | iClock Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `adms` | Easy | High | High-capacity camera + fingerprint ADMS device |
| **SpeedFace-V5L** | SpeedFace | ✅ | ✅ | ✅ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Very High | Fast touchless Visible Light face & palm reader |
| **SpeedFace-V5L [TD]** | SpeedFace | ✅ | ✅ | ✅ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | SpeedFace with thermal body temperature detection |

---

## 2. Hikvision (15 Models)

| Model | Series | FP | Face | Palm | RFID | Protocol / Port | ADMS | Driver | Difficulty | BD Market Availability | Notes |
|---|---|:---:|:---:|:---:|:---:|---|:---:|---|:---:|:---:|---|
| **DS-K1A8503** | DS-K1A Series | ✅ | ❌ | ❌ | ✅ | ISAPI HTTP `:80` | ❌ | `hikvision` | Medium | Very High | Standalone fingerprint time attendance terminal |
| **DS-K1A8503EF** | DS-K1A Series | ✅ | ❌ | ❌ | ✅ | ISAPI HTTP `:80` | ❌ | `hikvision` | Medium | High | EM Card + optical fingerprint sensor with ISAPI |
| **DS-K1A8503MF** | DS-K1A Series | ✅ | ❌ | ❌ | ✅ | ISAPI HTTP `:80` | ❌ | `hikvision` | Medium | High | Mifare card + fingerprint ISAPI terminal |
| **DS-K1T341AMF** | DS-K1T Series | ✅ | ✅ | ❌ | ✅ | ISAPI HTTP `:80` | ❌ | `hikvision` | Medium | Very High | Face recognition terminal with deep learning |
| **DS-K1T341CMF** | DS-K1T Series | ✅ | ✅ | ❌ | ✅ | ISAPI HTTP `:80` | ❌ | `hikvision` | Medium | High | 4.3-inch touch screen face recognition terminal |
| **DS-K1T343MFWX** | DS-K1T Series | ❌ | ✅ | ❌ | ✅ | ISAPI HTTP `:80` | ❌ | `hikvision` | Medium | Very High | Face recognition terminal with Wi-Fi & Hik-Connect |
| **DS-K1T343MWX** | DS-K1T Series | ❌ | ✅ | ❌ | ✅ | ISAPI HTTP `:80` | ❌ | `hikvision` | Medium | High | Compact face recognition terminal with Wi-Fi |
| **DS-K1T671MF** | DS-K1T Pro | ✅ | ✅ | ❌ | ✅ | ISAPI HTTP `:80` | ❌ | `hikvision` | Medium | High | 7-inch enterprise face & fingerprint terminal |
| **DS-K1T671TM** | DS-K1T Pro | ❌ | ✅ | ❌ | ✅ | ISAPI HTTP `:80` | ❌ | `hikvision` | Medium | Medium | Thermographic temperature screening face terminal |
| **DS-K1T671M** | DS-K1T Pro | ❌ | ✅ | ❌ | ✅ | ISAPI HTTP `:80` | ❌ | `hikvision` | Medium | High | 7-inch IPS touchscreen face terminal |
| **DS-K1T320M** | DS-K1T Value | ❌ | ✅ | ❌ | ✅ | ISAPI HTTP `:80` | ❌ | `hikvision` | Medium | Very High | Value series face recognition access control |
| **DS-K1T804** | DS-K1T804 | ✅ | ❌ | ❌ | ✅ | ISAPI HTTP `:80` | ❌ | `hikvision` | Medium | Very High | Optical fingerprint & RFID card terminal with LCD |
| **DS-K1T804AMF** | DS-K1T804 | ✅ | ❌ | ❌ | ✅ | ISAPI HTTP `:80` | ❌ | `hikvision` | Medium | High | Mifare + fingerprint standalone terminal |
| **DS-K1T805** | DS-K1T805 | ✅ | ❌ | ❌ | ✅ | ISAPI HTTP `:80` | ❌ | `hikvision` | Medium | High | Vandal-resistant outdoor fingerprint terminal |
| **DS-K1T606** | DS-K1T606 | ✅ | ✅ | ❌ | ✅ | ISAPI HTTP `:80` | ❌ | `hikvision` | Medium | Medium | Wall-mounted face and fingerprint reader |

---

## 3. Anviz (21 Models)

| Model | Series | FP | Face | Palm | RFID | Protocol / Port | ADMS | Driver | Difficulty | BD Market Availability | Notes |
|---|---|:---:|:---:|:---:|:---:|---|:---:|---|:---:|:---:|---|
| **C2 Pro** | C Series | ✅ | ❌ | ❌ | ✅ | TCP `:5010` / B-comm | ✅ | `adms` | Medium | High | Dual-core processor with CrossChex Cloud & B-comm |
| **A350** | A Series | ✅ | ❌ | ❌ | ✅ | TCP `:5010` / Cloud | ✅ | `adms` | Medium | High | Cloud time attendance with Wi-Fi & WebServer |
| **A350C** | A Series | ✅ | ❌ | ❌ | ✅ | TCP `:5010` / Cloud | ✅ | `adms` | Medium | Medium | A350 with RFID card reader |
| **W1** | W Series | ✅ | ❌ | ❌ | ✅ | TCP `:5010` / Cloud | ✅ | `adms` | Medium | Very High | Color screen fingerprint with touch keypad |
| **W1 Pro** | W Series | ✅ | ❌ | ❌ | ✅ | TCP `:5010` / Cloud | ✅ | `adms` | Medium | High | Upgraded touch keypad fingerprint terminal |
| **W1C Pro** | W Series | ✅ | ❌ | ❌ | ✅ | TCP `:5010` / Cloud | ✅ | `adms` | Medium | Medium | Card + fingerprint with Cloud sync |
| **W2** | W Series | ✅ | ❌ | ❌ | ✅ | TCP `:5010` / Cloud | ✅ | `adms` | Medium | High | Color screen biometric time attendance terminal |
| **W2 Pro** | W Series | ✅ | ❌ | ❌ | ✅ | TCP `:5010` / Cloud | ✅ | `adms` | Medium | High | Wi-Fi enabled Linux based terminal |
| **VF30 Pro** | VF Series | ✅ | ❌ | ❌ | ✅ | TCP `:5010` / PoE | ✅ | `adms` | Medium | High | PoE powered access control & attendance device |
| **EP300** | EP Series | ✅ | ❌ | ❌ | ✅ | TCP `:5010` | ✅ | `adms` | Medium | High | Classic desktop/wallmount fingerprint reader |
| **EP300 Pro** | EP Series | ✅ | ❌ | ❌ | ✅ | TCP `:5010` / Wi-Fi | ✅ | `adms` | Medium | High | Rechargeable battery + Wi-Fi cloud terminal |
| **EP30** | EP Series | ✅ | ❌ | ❌ | ✅ | TCP `:5010` / USB | ❌ | `adms` | Medium | Medium | Compact entry-level fingerprint machine |
| **CX2** | CX Series | ✅ | ❌ | ❌ | ✅ | CrossChex Cloud | ✅ | `adms` | Medium | High | Linux-based CrossChex Cloud terminal |
| **CX2 Lite** | CX Series | ✅ | ❌ | ❌ | ✅ | CrossChex Cloud | ✅ | `adms` | Medium | Medium | Cost-effective smart cloud time attendance |
| **CX7** | CX Series | ✅ | ❌ | ❌ | ✅ | CrossChex Cloud | ✅ | `adms` | Medium | Medium | 7-inch touchscreen biometric station |
| **FaceDeep 3** | FaceDeep | ❌ | ✅ | ❌ | ✅ | AI Camera / Cloud | ✅ | `adms` | Medium | High | AI dual-camera face recognition terminal |
| **FaceDeep 3 IRT** | FaceDeep | ❌ | ✅ | ❌ | ✅ | AI Camera / Thermal | ✅ | `adms` | Medium | Medium | FaceDeep 3 with infrared temperature detection |
| **FaceDeep 5** | FaceDeep | ❌ | ✅ | ❌ | ✅ | AI Camera / Cloud | ✅ | `adms` | Medium | High | 50,000 capacity enterprise face terminal |
| **FaceDeep 5 IRT** | FaceDeep | ❌ | ✅ | ❌ | ✅ | AI Camera / Thermal | ✅ | `adms` | Medium | Medium | Enterprise thermal face terminal |
| **FacePass 7 Pro** | FacePass | ❌ | ✅ | ❌ | ✅ | AI Camera / Cloud | ✅ | `adms` | Medium | High | Smart IR facial recognition with live detection |
| **FacePass 7 Pro IRT** | FacePass | ❌ | ✅ | ❌ | ✅ | AI Camera / Thermal | ✅ | `adms` | Medium | Medium | FacePass 7 Pro with thermal camera sensor |

---

## 4. FingerTec (18 Models)

> FingerTec terminals natively utilize the **ZKTeco TCP/UDP `:4370` protocol** and ADMS push, allowing direct integration using `AttendanceHub::provider('fingertec')` or `AttendanceHub::model('TA500')`.

| Model | Series | FP | Face | Palm | RFID | Protocol / Port | ADMS | Driver | Difficulty | BD Market Availability | Notes |
|---|---|:---:|:---:|:---:|:---:|---|:---:|---|:---:|:---:|---|
| **TA300** | TA Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | TCP/IP and USB fingerprint attendance terminal |
| **TA500** | TA Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Very High | Extremely popular office fingerprint system |
| **TA700W** | TA Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Wi-Fi enabled fingerprint attendance machine |
| **TA100C** | TA Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Color screen time recorder |
| **TA200 Plus** | TA Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Multimedia fingerprint & card terminal |
| **AC100C** | AC Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ❌ | `zkteco` | Easy | Medium | Access control & time attendance machine |
| **AC900** | AC Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ❌ | `zkteco` | Easy | Medium | Rugged door access control fingerprint device |
| **R2** | R Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ❌ | `zkteco` | Easy | High | Door access control and time attendance |
| **R3** | R Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ❌ | `zkteco` | Easy | Medium | Upgraded optical sensor master reader |
| **H2i** | H Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ❌ | `zkteco` | Easy | Medium | Compact master access control & punch recorder |
| **Q2i** | Q Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Color multimedia terminal with voice prompts |
| **Kadex** | Kadex | ❌ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ❌ | `zkteco` | Easy | Medium | Dedicated RFID card time attendance |
| **m-Kadex** | Kadex | ❌ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ❌ | `zkteco` | Easy | Medium | Slim weather-resistant card terminal |
| **Face ID 2** | Face ID | ✅ | ✅ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Face, fingerprint & password multi-biometric |
| **Face ID 3** | Face ID | ❌ | ✅ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Dedicated touchless face recognition |
| **Face ID 4** | Face ID | ✅ | ✅ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | High accuracy facial verification terminal |
| **Face ID 4d** | Face ID | ❌ | ✅ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Medium | Door access oriented facial scanner |
| **Face ID X** | Face ID | ✅ | ✅ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Flagship fast visible face and fingerprint reader |

---

## 5. eSSL (22 Models)

> eSSL is the dominant biometric hardware brand across India, Bangladesh, and South Asia. Most eSSL models support **ADMS HTTP Push** out of the box.

| Model | Series | FP | Face | Palm | RFID | Protocol / Port | ADMS | Driver | Difficulty | BD Market Availability | Notes |
|---|---|:---:|:---:|:---:|:---:|---|:---:|---|:---:|:---:|---|
| **X990** | X Series | ✅ | ❌ | ❌ | ✅ | ADMS Push / TCP | ✅ | `adms` | Easy | Very High | Standalone fingerprint with push data protocol |
| **iClock990** | iClock | ✅ | ❌ | ❌ | ✅ | ADMS Push / TCP | ✅ | `adms` | Easy | Very High | Camera + fingerprint high capacity terminal |
| **VEGA+W+POE** | VEGA | ✅ | ❌ | ❌ | ✅ | ADMS Push / TCP | ✅ | `adms` | Easy | High | PoE powered fingerprint time attendance |
| **F18** | F Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Very High | Classic access control and attendance terminal |
| **F22+ID+WIFI** | F Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Very High | Ultra-thin touch keypad biometric reader |
| **SF100** | SF Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | IP based fingerprint terminal |
| **K30PRO** | K Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Very High | Fingerprint with in-built battery backup |
| **FR1200** | FR Series | ✅ | ❌ | ❌ | ✅ | RS485 Slave | ❌ | `webhook_bridge` | Easy | High | RS485 slave fingerprint reader |
| **X7** | X Series | ✅ | ❌ | ❌ | ✅ | Standalone Reader | ❌ | `webhook_bridge` | Easy | Medium | Keypad and fingerprint standalone lock reader |
| **P160** | Palm Series | ✅ | ❌ | ✅ | ✅ | ADMS Push / TCP | ✅ | `adms` | Easy | High | Palm and fingerprint multi-biometric |
| **Silk-FP-101TA** | Silk Series | ✅ | ❌ | ❌ | ✅ | ADMS Push / TCP | ✅ | `adms` | Easy | High | SilkID sensor for wet, rough, dry fingers |
| **WL20** | WL Series | ✅ | ❌ | ❌ | ❌ | ADMS Push / Wi-Fi | ✅ | `adms` | Easy | Very High | Wi-Fi fingerprint time attendance terminal |
| **Aiface Vesta+POE** | Aiface | ❌ | ✅ | ❌ | ✅ | ADMS Push / TCP | ✅ | `adms` | Easy | Very High | AI face recognition with visible light technology |
| **AIFACE SUN+POE** | Aiface | ✅ | ✅ | ❌ | ✅ | ADMS Push / TCP | ✅ | `adms` | Easy | High | Sunlight readable AI face & fingerprint reader |
| **AIFACE VIKTOR** | Aiface | ✅ | ✅ | ✅ | ✅ | ADMS Push / TCP | ✅ | `adms` | Easy | High | Face, palm and fingerprint attendance terminal |
| **EFACE990** | EFace | ✅ | ✅ | ❌ | ✅ | ADMS Push / TCP | ✅ | `adms` | Easy | Very High | Touch screen multi-biometric time attendance |
| **UFACE302** | UFace | ✅ | ✅ | ❌ | ✅ | ADMS Push / TCP | ✅ | `adms` | Easy | Very High | Multi-biometric facial identification device |
| **MB160** | MB Series | ✅ | ✅ | ❌ | ✅ | ADMS Push / TCP | ✅ | `adms` | Easy | Very High | Fingerprint & face recognition terminal |
| **SFace900** | SFace | ✅ | ✅ | ❌ | ✅ | ADMS Push / TCP | ✅ | `adms` | Easy | High | Semi-outdoor visible light face & fingerprint |
| **K90 Pro** | K Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Very High | Standard biometric fingerprint terminal |
| **K21 Pro** | K Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | High speed verification fingerprint reader |
| **eSSL9500** | Enterprise | ✅ | ❌ | ❌ | ✅ | ADMS Push / TCP | ✅ | `adms` | Easy | High | Large enterprise capacity terminal |

---

## 6. VIRDI (12 Models)

| Model | Series | FP | Face | Palm | RFID | Protocol / Port | ADMS | Driver | Difficulty | BD Market Availability | Notes |
|---|---|:---:|:---:|:---:|:---:|---|:---:|---|:---:|:---:|---|
| **AC-5000** | AC Series | ✅ | ❌ | ❌ | ✅ | TCP/IP `:9870` / PoE | ❌ | `webhook_bridge` | Medium | High | IP65 waterproof fingerprint terminal with PoE |
| **AC-6000** | AC Series | ✅ | ❌ | ❌ | ✅ | TCP/IP `:9870` | ❌ | `webhook_bridge` | Medium | High | Color touchscreen camera fingerprint terminal |
| **AC-2100** | AC Series | ✅ | ❌ | ❌ | ✅ | TCP/IP `:9870` | ❌ | `webhook_bridge` | Medium | High | IPX3 rated access control & time attendance |
| **AC-2200** | AC Series | ✅ | ❌ | ❌ | ✅ | TCP/IP `:9870` | ❌ | `webhook_bridge` | Medium | High | Smart card and fingerprint with Bluetooth |
| **AC-4000** | AC Series | ✅ | ❌ | ❌ | ✅ | TCP/IP `:9870` | ❌ | `webhook_bridge` | Medium | Medium | Enterprise fingerprint voice prompt terminal |
| **AC-5100** | AC Series | ✅ | ❌ | ❌ | ✅ | TCP/IP `:9870` | ❌ | `webhook_bridge` | Medium | Medium | Graphic LCD outdoor fingerprint terminal |
| **AC-7000** | AC Series | ✅ | ✅ | ❌ | ✅ | TCP/IP `:9870` | ❌ | `webhook_bridge` | Medium | High | Dual face and fingerprint luxury terminal |
| **AC-2100 Plus** | AC Series | ✅ | ❌ | ❌ | ✅ | TCP/IP `:9870` | ❌ | `webhook_bridge` | Medium | High | Fake fingerprint detection with IP65 rating |
| **AC-5000 Plus** | AC Series | ✅ | ❌ | ❌ | ✅ | TCP/IP `:9870` | ❌ | `webhook_bridge` | Medium | Medium | Heavy duty factory access control terminal |
| **UBio-X Face** | UBio Series | ❌ | ✅ | ❌ | ✅ | UNIS REST / TCP | ❌ | `webhook_bridge` | Medium | Medium | High speed walk-through facial recognition |
| **UBio-X Iris** | UBio Series | ✅ | ✅ | ❌ | ✅ | UNIS REST / Iris | ❌ | `webhook_bridge` | High | Low | Military grade iris and fingerprint scanner |
| **AC-2000** | AC Series | ✅ | ❌ | ❌ | ✅ | Bluetooth / TCP | ❌ | `webhook_bridge` | Medium | Medium | Smartphone key & fingerprint reader |

---

## 7. Suprema (18 Models)

> Integrated via the **Suprema BioStar 2 REST API** (`https://server:443`).

| Model | Series | FP | Face | Palm | RFID | Protocol / Port | ADMS | Driver | Difficulty | BD Market Availability | Notes |
|---|---|:---:|:---:|:---:|:---:|---|:---:|---|:---:|:---:|---|
| **BioStation 2** | BioStation | ✅ | ❌ | ❌ | ✅ | BioStar 2 REST `:443` | ❌ | `suprema` | Medium | High | Flagship high performance fingerprint terminal |
| **BioStation 3** | BioStation | ❌ | ✅ | ❌ | ✅ | BioStar 2 REST `:443` | ❌ | `suprema` | Medium | High | AI facial recognition with QR & mobile credentials |
| **BioStation 2a** | BioStation | ✅ | ❌ | ❌ | ✅ | BioStar 2 REST `:443` | ❌ | `suprema` | Medium | High | Deep-learning based high capacity fingerprint reader |
| **BioStation A2** | BioStation | ✅ | ❌ | ❌ | ✅ | BioStar 2 REST `:443` | ❌ | `suprema` | Medium | High | Quad-core CPU with OPOS optical sensor |
| **BioStation L2** | BioStation | ✅ | ❌ | ❌ | ✅ | BioStar 2 REST `:443` | ❌ | `suprema` | Medium | Very High | Cost-effective high accuracy fingerprint terminal |
| **BioEntry W2** | BioEntry | ✅ | ❌ | ❌ | ✅ | BioStar 2 REST `:443` | ❌ | `suprema` | Medium | Very High | IP67/IK09 vandal-proof outdoor fingerprint terminal |
| **BioEntry W3** | BioEntry | ❌ | ✅ | ❌ | ✅ | BioStar 2 REST `:443` | ❌ | `suprema` | Medium | High | Next-gen AI face recognition reader |
| **BioEntry P2** | BioEntry | ✅ | ❌ | ❌ | ✅ | BioStar 2 REST `:443` | ❌ | `suprema` | Medium | High | Mullion-type slim indoor fingerprint terminal |
| **BioEntry R2** | BioEntry | ✅ | ❌ | ❌ | ✅ | RS-485 Slave | ❌ | `suprema` | Medium | Medium | RS-485 slave fingerprint reader |
| **BioLite N2** | BioLite | ✅ | ❌ | ❌ | ✅ | BioStar 2 REST `:443` | ❌ | `suprema` | Medium | Very High | Outdoor IP67 keypad fingerprint terminal |
| **FaceLite** | FaceStation | ❌ | ✅ | ❌ | ✅ | BioStar 2 REST `:443` | ❌ | `suprema` | Medium | High | Compact touchless facial recognition reader |
| **FaceStation 2** | FaceStation | ❌ | ✅ | ❌ | ✅ | BioStar 2 REST `:443` | ❌ | `suprema` | Medium | High | Ultra performance facial recognition terminal |
| **FaceStation F2** | FaceStation | ✅ | ✅ | ❌ | ✅ | BioStar 2 REST `:443` | ❌ | `suprema` | Medium | High | Fusion multi-modal face and fingerprint reader |
| **XPass 2** | XPass | ❌ | ❌ | ❌ | ✅ | BioStar 2 REST `:443` | ❌ | `suprema` | Medium | High | Outdoor RFID smart card reader |
| **XPass D2** | XPass | ❌ | ❌ | ❌ | ✅ | RS-485 Slave | ❌ | `suprema` | Medium | Medium | Mullion-type RFID card reader |
| **X-Station 2** | X-Station | ❌ | ❌ | ❌ | ✅ | BioStar 2 REST `:443` | ❌ | `suprema` | Medium | High | Versatile QR code & RFID intelligent terminal |
| **CoreStation** | Controller | ❌ | ❌ | ❌ | ✅ | BioStar 2 REST `:443` | ❌ | `suprema` | Medium | High | Intelligent biometric door controller hub |
| **BioEntry W** | BioEntry | ✅ | ❌ | ❌ | ✅ | BioStar 2 REST `:443` | ❌ | `suprema` | Medium | High | Vandal resistant PoE outdoor terminal |

---

## 8. Deli (10 Models)

| Model | Series | FP | Face | Palm | RFID | Protocol / Port | ADMS | Driver | Difficulty | BD Market Availability | Notes |
|---|---|:---:|:---:|:---:|:---:|---|:---:|---|:---:|:---:|---|
| **E3960** | E Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | USB/LAN fingerprint attendance machine |
| **E3765** | E Series | ✅ | ❌ | ❌ | ❌ | TCP/UDP `:4370` | ❌ | `zkteco` | Easy | High | Color LCD fingerprint machine |
| **E13750** | E Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Medium | Network biometric time attendance machine |
| **ES152** | ES Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Smart biometric terminal with Wi-Fi |
| **ES151** | ES Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Voice prompt fingerprint clock-in machine |
| **ES161** | ES Series | ✅ | ✅ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Face and fingerprint hybrid machine |
| **ES171** | ES Series | ✅ | ✅ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Infrared face and fingerprint terminal |
| **ES172** | ES Series | ✅ | ✅ | ✅ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Medium | Face, palm vein, fingerprint 100K capacity |
| **E3747** | E Series | ✅ | ❌ | ❌ | ❌ | TCP/UDP `:4370` | ❌ | `zkteco` | Easy | Medium | Budget fingerprint attendance recorder |
| **E3758** | E Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ❌ | `zkteco` | Easy | Medium | Network time attendance machine with ID card |

---

## 9. Granding (15 Models)

> Granding terminals utilize the **ZKTeco socket and ADMS protocols**, enabling zero-friction integration via `AttendanceHub::provider('granding')` or `AttendanceHub::model('GT100')`.

| Model | Series | FP | Face | Palm | RFID | Protocol / Port | ADMS | Driver | Difficulty | BD Market Availability | Notes |
|---|---|:---:|:---:|:---:|:---:|---|:---:|---|:---:|:---:|---|
| **GT100** | GT Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | High speed fingerprint time attendance |
| **GT110** | GT Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Medium | Compact biometric attendance terminal |
| **GT2100** | GT Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Multimedia fingerprint & card terminal |
| **GT2100F** | GT Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Medium | Optical fingerprint with camera snapshot |
| **GT300** | GT Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Wireless GPRS/Wi-Fi fingerprint terminal |
| **GT800** | GT Series | ✅ | ❌ | ✅ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Palm and fingerprint terminal with battery |
| **GT-1000** | GT Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Medium | High security biometric recorder |
| **T5** | T Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Slim fingerprint access control terminal |
| **T6** | T Series | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ❌ | `zkteco` | Easy | Medium | Door access biometric unit |
| **FA1** | FA Series | ✅ | ✅ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Very High | Popular hybrid face and fingerprint terminal |
| **FA2** | FA Series | ✅ | ✅ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Dual infrared camera facial verification |
| **FA1-H** | FA Series | ✅ | ✅ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | High capacity face and RFID terminal |
| **BioStation** | Granding Bio | ✅ | ❌ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Medium | Granding enterprise attendance station |
| **FacePro** | FacePro | ✅ | ✅ | ✅ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | High | Visible light touchless face and palm recognition |
| **BioFace** | BioFace | ✅ | ✅ | ❌ | ✅ | TCP/UDP `:4370` | ✅ | `zkteco` | Easy | Medium | High accuracy facial verification unit |

---

## 10. Soyal (15 Models)

> Soyal access controllers connect directly through the **Webhook / Wiegand Bridge** or through TCP/IP serial converters (AR-321CM / AR-727CM) via `AttendanceHub::provider('soyal')`.

| Model | Series | FP | Face | Palm | RFID | Protocol / Port | ADMS | Driver | Difficulty | BD Market Availability | Notes |
|---|---|:---:|:---:|:---:|:---:|---|:---:|---|:---:|:---:|---|
| **AR-837EF** | AR Series | ✅ | ❌ | ❌ | ✅ | TCP/IP `:1621` | ❌ | `webhook_bridge` | Medium | High | LCD fingerprint access controller with TCP/IP |
| **AR-888** | AR Series | ❌ | ❌ | ❌ | ✅ | RS-485 / Wiegand | ❌ | `webhook_bridge` | Medium | High | European-style smart card proximity reader |
| **AR-888UL** | AR Series | ❌ | ❌ | ❌ | ✅ | Wiegand Output | ❌ | `webhook_bridge` | Medium | Medium | Wiegand illuminated RFID reader |
| **AR-725E** | AR Series | ❌ | ❌ | ❌ | ✅ | TCP/IP `:1621` | ❌ | `webhook_bridge` | Medium | Very High | Touch-panel access controller with TCP/IP |
| **AR-725H** | AR Series | ❌ | ❌ | ❌ | ✅ | RS-485 | ❌ | `webhook_bridge` | Medium | High | RS-485 touch keypad controller |
| **AR-829E** | AR Series | ❌ | ❌ | ❌ | ✅ | TCP/IP `:1621` | ❌ | `webhook_bridge` | Medium | High | Graphic LCD display TCP/IP controller |
| **AR-331** | AR Series | ❌ | ❌ | ❌ | ✅ | Wiegand / RS-485 | ❌ | `webhook_bridge` | Medium | Medium | Waterproof metal casing proximity reader |
| **AR-321CM** | Converter | ❌ | ❌ | ❌ | ❌ | RS-485 to TCP/IP | ❌ | `webhook_bridge` | Medium | High | RS-485 to TCP/IP Ethernet converter |
| **AR-888U** | AR Series | ❌ | ❌ | ❌ | ✅ | Wiegand Output | ❌ | `webhook_bridge` | Medium | Medium | US-style flush mount proximity reader |
| **AR-725** | AR Series | ❌ | ❌ | ❌ | ✅ | Proximity Reader | ❌ | `webhook_bridge` | Medium | High | Illuminated touch keypad reader |
| **AR-721H** | AR Series | ❌ | ❌ | ❌ | ✅ | RS-485 | ❌ | `webhook_bridge` | Medium | Very High | Most common Taiwanese RS-485 card controller |
| **AR-757H** | AR Series | ❌ | ❌ | ❌ | ✅ | RS-485 / Wiegand | ❌ | `webhook_bridge` | Medium | High | Metal keypad durable controller |
| **AR-829** | AR Series | ❌ | ❌ | ❌ | ✅ | RS-485 | ❌ | `webhook_bridge` | Medium | Medium | LCD standalone access controller |
| **AR-888S** | AR Series | ❌ | ❌ | ❌ | ✅ | Wiegand Output | ❌ | `webhook_bridge` | Medium | Medium | Square touch keypad proximity device |
| **AR-727H** | AR Series | ❌ | ❌ | ❌ | ✅ | RS-485 | ❌ | `webhook_bridge` | Medium | High | Backlit LCD master access controller |

---

## 💻 Programmatic Usage with Models

### 1. Connecting Directly by Model Name

Instead of guessing what protocol or port a device requires, pass the model name directly:

```php
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;

// The package looks up UA860, knows it's ZKTeco port 4370, and connects
$driver = AttendanceHub::model('UA860')
    ->connect(['ip' => '192.168.1.201']);

// Works for any of the 166 models across all 10 brands
$hikDriver = AttendanceHub::model('DS-K1T341AMF')->connect(['ip' => '192.168.1.150']);
$supremaDriver = AttendanceHub::model('BioStation 2')->connect(['ip' => '192.168.1.180']);
```

### 2. Querying the Model Catalog in PHP

```php
use ImranDevBd\AttendanceHub\Support\DeviceCatalog;

// Find details for any model
$model = DeviceCatalog::find('SpeedFace-V5L');
/*
[
    'provider' => 'ZKTeco',
    'model' => 'SpeedFace-V5L',
    'series' => 'SpeedFace',
    'fp' => true,
    'face' => true,
    'palm' => true,
    'rfid' => true,
    'port' => 4370,
    'driver' => 'zkteco',
    ...
]
*/

// Get all models for a provider
$esslModels = DeviceCatalog::forProvider('essl');

// Search catalog
$matching = DeviceCatalog::search('Face');
```

### 3. Browsing Catalog via Artisan CLI

```bash
# List all 166 models
php artisan attendance:catalog

# Filter by brand
php artisan attendance:catalog --provider=hikvision

# Search by keyword
php artisan attendance:catalog --search=SpeedFace
```
