
#include <TFT_eSPI.h>
#include <DHTesp.h>
#include <WiFi.h>
#include <HTTPClient.h>

// --------------------- TFT ------------------------
TFT_eSPI tft = TFT_eSPI();
#define SCREEN_WIDTH 320
#define SCREEN_HEIGHT 240

// -------------------- SENSORES --------------------
#define DHTPIN 22
#define SOUND_AO 35
#define PIR_PIN  27

DHTesp dht;

// -------------------- WIFI ------------------------
const char* ssid     = "mainol";
const char* password = "BryantMyers67";

// -------------------- API PHP ---------------------
const char* serverURL = "http://192.168.71.230/guardar.php";

// -------------------- VALORES ---------------------
float lastTemp = NAN;
float lastHum  = NAN;
int   lastSound = 0;
int   lastDetect = 0;
int   lastPIR = 0;

const int SOUND_THRESHOLD = 1500;

// --------------------------------------------------
// DIBUJAR PANTALLA
void printScreen() {
  tft.fillScreen(TFT_WHITE);
  tft.setTextColor(TFT_BLACK, TFT_WHITE);
  tft.setTextSize(1);

  int cx = SCREEN_WIDTH / 2;

  tft.drawCentreString("Temp: " + (isnan(lastTemp) ? String("--.-") : String(lastTemp, 1)) + " C", cx, 20, 2);
  tft.drawCentreString("Hum:  " + (isnan(lastHum)  ? String("--.-") : String(lastHum, 1)) + " %", cx, 60, 2);
  tft.drawCentreString("Sound: " + String(lastSound), cx, 100, 2);
  tft.drawCentreString("Detect: " + String(lastDetect ? "YES" : "NO"), cx, 140, 2);
  tft.drawCentreString("Motion: " + String(lastPIR ? "YES" : "NO"), cx, 180, 2);

  String wifiTxt = (WiFi.status() == WL_CONNECTED)
                   ? "WiFi OK  IP " + WiFi.localIP().toString()
                   : "WiFi: NO CONNECTION";

  tft.drawCentreString(wifiTxt, cx, 215, 2);
}

// --------------------------------------------------
// CONECTAR WIFI
void connectWifi() {
  Serial.println("Conectando a WiFi...");
  WiFi.begin(ssid, password);
  int cont = 0;

  while (WiFi.status() != WL_CONNECTED && cont < 30) {
    delay(500);
    Serial.print(".");
    cont++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi CONECTADO");
    Serial.print("IP: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nNO se pudo conectar a WiFi");
  }

  printScreen();
}

// --------------------------------------------------
// ENVIAR DATOS A PHP -> MARIADB
void sendToServer(float temp, float hum, int sound, int detect, int pir) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("No se envia: WiFi NO conectado");
    return;
  }

  HTTPClient http;
  Serial.print("Conectando a: ");
  Serial.println(serverURL);

  http.begin(serverURL);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String data =
    "temperatura=" + String(temp) +
    "&humedad="   + String(hum) +
    "&sonido="    + String(sound) +
    "&detect="    + String(detect) +
    "&pir="       + String(pir);

  Serial.print("POST data: ");
  Serial.println(data);

  int code = http.POST(data);
  Serial.print("Respuesta servidor (HTTP code): ");
  Serial.println(code);

  String respuesta = http.getString();
  Serial.print("Cuerpo respuesta: ");
  Serial.println(respuesta);

  http.end();
}

// --------------------------------------------------
void setup() {
  Serial.begin(115200);
  delay(1000);   // pequeño delay para que el puerto serie se estabilice
  Serial.println("\n\n===== ESP32 INICIANDO =====");

  tft.init();
  tft.setRotation(1);

  // inicializamos dht por si queremos volver a usarlo más tarde (no obligatorio)
  dht.setup(DHTPIN, DHTesp::DHT11);
  pinMode(PIR_PIN, INPUT);

  // seed para random
  randomSeed(micros() ^ (uint32_t)ESP.getEfuseMac()); // buena semilla en ESP32

  connectWifi();
  delay(1500);
  printScreen();
}

// --------------------------------------------------
// Generador de valores aleatorios razonables
float randomTemperature() {
  // rango 18.0 - 26.0 C
  int v = random(180, 261); // 180..260
  return v / 10.0;
}
float randomHumidity() {
  // rango 30.0 - 65.0 %
  int v = random(300, 651); // 300..650
  return v / 10.0;
}

// --------------------------------------------------
unsigned long lastMillis = 0;
const unsigned long interval = 10000; // cada 10 s para que se vea claro

void loop() {
  if (millis() - lastMillis >= interval) {
    lastMillis = millis();

    Serial.println("\n--- LOOP ---");

    // ----------- DHT SIMULADO -----------
    // Generamos valores random y los usamos como "lectura"
    lastTemp = randomTemperature();
    lastHum  = randomHumidity();
    Serial.print("Temp: ");
    Serial.print(lastTemp);
    Serial.print("  Hum: ");
    Serial.println(lastHum);

    // ----------- SONIDO -----------
    lastSound = analogRead(SOUND_AO);
    lastDetect = (lastSound > SOUND_THRESHOLD) ? 1 : 0;
    Serial.print("Sound: ");
    Serial.print(lastSound);
    Serial.print("  Detect: ");
    Serial.println(lastDetect);

    // ----------- PIR -----------
    lastPIR = digitalRead(PIR_PIN);
    Serial.print("PIR: ");
    Serial.println(lastPIR);

    // ----------- ENVIAR -----------
    if (!isnan(lastTemp) && !isnan(lastHum)) {
      Serial.println("Enviando datos al servidor...");
      sendToServer(lastTemp, lastHum, lastSound, lastDetect, lastPIR);
    } else {
      Serial.println("No se envian datos porque temp/hum son NaN");
    }

    // ----------- TFT ------------
    printScreen();
  }
}
