# ERRONKA2

Cpuspus, Oyer, Oyer2(el que pierde clash royale) y la mujer

Interfaces de red:

1) Default - Internet entre los 2 pFsense
2) ASIR2-T21 - DMZ 
3) ASIR2-T22 - Bezeros
4) ASIR2-T23 - Zerbitzariak
5) ASIR2-T24 - Mainol
6) Vlan71 - Cluster

Las interfaces de ASIR-TX van a ser 10.x, 20.x, 30.x y 40.x


# Enpresaren planteamendua

 ## Puneta.lan

 DNS,DHCP

 ### Erabiltzaileak
 OU: Puneteroak eta Bezeroak

 Taldeak: 
 Puneteroak(Marketing, Administrazioa, Datubase admin, Docker-Users)
 Bezeroak(Ikaslea, Irakasleak)
Para buscar la intranet la url sera intranet.puneta.lan


# IP-ak:

192.168.30.40 --> ubuntu server, arduinoaren datuak gordetzeko mysql

192.168.30.2 --> Windows Server DFS egiteko 

192.168.71.114 --> TrueNAS con raid5
