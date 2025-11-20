// ---------------------------------------------------------------------
// 1. CONFIGURACIÓN DE PINES Y LIBRERÍA
// ---------------------------------------------------------------------
#include <LiquidCrystal.h>
const int rs = 7, en = 8, d4 = 9, d5 = 10, d6 = 11, d7 = 12;
LiquidCrystal lcd(rs, en, d4, d5, d6, d7);

// Pines de Sensores
const int pinPIR = 2;       
const int pinSonido = A0;   

// Variables para el control de tiempo (Milisegundos)
unsigned long tiempoInicioMovimiento = 0;
const long duracionMostrarSI = 5000; // Mostrar "SI" por 5 segundos (5000 ms)

// ---------------------------------------------------------------------
// 2. FUNCIÓN SETUP 
// ---------------------------------------------------------------------
void setup() {
  lcd.begin(16, 2);
  lcd.print("Iniciando...");
  pinMode(pinPIR, INPUT); 
  delay(3000); 
  lcd.clear();
}

// ---------------------------------------------------------------------
// 3. FUNCIÓN LOOP 
// ---------------------------------------------------------------------
void loop() {
  
  // --- LECTURA DE SENSORES ---
  int estadoPIR = digitalRead(pinPIR);
  int valorSonido = analogRead(pinSonido);

  // --- LÓGICA DE TIEMPO Y MOVIMIENTO ---

  if (estadoPIR == HIGH) {
    // Si se detecta movimiento, guardamos el tiempo actual.
    tiempoInicioMovimiento = millis();
  }

  // Comprueba si el tiempo de visualización de "SI" ha terminado.
  bool movimientoDetectadoRecientemente = (millis() - tiempoInicioMovimiento < duracionMostrarSI);

  // --- ACTUALIZACIÓN DE PANTALLA ---

  // LÍNEA 1: ESTADO DE MOVIMIENTO
  lcd.setCursor(0, 0); 
  lcd.print("MOVIMIENTO: ");
  
  if (movimientoDetectadoRecientemente) {
    // Muestra "SI" si se detectó movimiento en los últimos 5 segundos
    lcd.print("SI "); 
  } else {
    // Borra el "SI" y muestra "NO" si no ha habido movimiento reciente
    lcd.print("NO ");
  }

  // LÍNEA 2: RUIDO
  lcd.setCursor(0, 1);
  lcd.print("RUIDO: ");
  lcd.print(valorSonido); 
  lcd.print("    "); // Limpia el final de la línea

  // Pausa breve para que la lectura del sonido sea estable
  delay(500); 
}
