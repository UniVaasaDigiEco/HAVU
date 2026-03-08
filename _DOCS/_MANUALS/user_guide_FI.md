# HAVU Gamification — Käyttöopas

## Yleiskatsaus

HAVU Gamification on sijaintipohjainen ulkoilupelialusta. Ylläpitäjät voivat luoda reittejä lisäämällä kartalle rasteja. Rasteille voi syöttää sisältöä (esim. Infoa lähiympäristön luonnosta, eläimistä, nähtävyyksistä, jne.), ja pelaajat seuraavat näitä reittejä oikeassa maailmassa puhelimensa GPS:n avulla.

Voit kirjautua HAVU Gamificationiin osoitteessa [https://jansoftworks.fi/HavuGamification/](https://jansoftworks.fi/HavuGamification/) 

Käyttäjätiedot on toimitettu erikseen. Klikkaa kirjaudu sisään ja käytä saamiasi tunnuksia.

---

## Ylläpitäjille

**Kaikki ylläpitosivut vaativat sisäänkirjautumisen.**

### Hallintapaneeli (pages/admin/dashboard.php)

Päänäkymä. Täältä voit:

- Siirtyä reittien hallintaan (1: luominen, 2: muokkaaminen, 3: poistaminen).

    ![Reittien hallinta](./user_guide_ss_1.png "Reittien hallinta; 1. Luominen, 2. Muokkaaminen, 3. Poistaminen")

- Testata reittiä: 
  - 1: Valitse reitti pudotusvalikosta
  - 2: Klikkaa Pelaa avataksesi sen pelitilassa.
    
  ![Reitin testaus](./user_guide_ss_2.png "Reitin testaus; 1. Valitse reitti, 2. Klikkaa Pelaa")

---

### Reitin luominen (pages/admin/new-route.php)

Reitin ensimmäinen rasti toimii lähtöpisteenä ja viimeinen rasti maalina. Voit muuttaa rastien järjestystä listalla. Rastit kuljetaan reitillä listan järjestyksessä ylhäältä alas.

1. Syötä reitin nimi (pakollinen) ja valinnainen kuvaus.
2. Julkaisupäivämäärä asetetaan automaattisesti tähän päivään.
    
   ![Reitin luominen](./user_guide_ss_3.png "Reitin luominen; 1. Reitin nimi ja kuvaus, 2. Julkaisupäivämäärä")

3. Klikkaa karttaa lisätäksesi rasteja. Rastien muokkausnäkymä avautuu. (1)
4. Täytä jokaiselle rastille:
   - 2: Rastin otsikko (pakollinen)
   - 3: Rastin sisältö — teksti, joka näytetään pelaajille, kun he saapuvat paikalle
   - 4: Tallenna rastin tiedot.
   - **HUOM! Jos klikkaat "Peruuta", rastin tietoja ei tallenneta, mutta merkki jää kartalle. Voit klikata sitä uudestaan myöhemmin ja syöttää tiedot. Tai klikata rastilistalta roskakorikuvaketta poistaaksesi sen kokonaan.**

   ![Rastin lisääminen kartalle](./user_guide_ss_4.png "Rastin lisääminen kartalle; 1. Klikkaa karttaa")
   ![Rastin muokkausnäkymä](./user_guide_ss_5.png "Rastin muokkausnäkymä; 2. Rastin otsikko, 3. Rastin sisältö, 4. Tallenna rasti")

5. Käytä nuolipainikkeita rastien järjestyksen muuttamiseen.
6. Käytä roskakorikuvaketta rastin poistamiseen.
7. Vedä merkkiä kartalla hienosäätääksesi sen sijaintia.

    ![Rastien järjestyksen muuttaminen ja poistaminen](./user_guide_ss_6.png "Rastien tietojen muokkaus, rastin poistaminen ja järestyksen muuttaminen")

8. Klikkaa Luo reitti, kun olet valmis.
    ![Reitin luominen](./user_guide_ss_7.png "Reitin luominen; Klikkaa Luo reitti")

---

### Reitin muokkaaminen (pages/admin/edit-route.php)

Reitin muokkaus toimii samalla tavalla kuin luominen, mutta sinun on ensin ladattava olemassa oleva reitti muokkausnäkymään.

![Reitin muokkaaminen](./user_guide_ss_8.png "Reitin muokkaaminen")

1. Valitse muokattava reitti "Valitse muokattava reitti" -pudotusvalikosta ja klikkaa Lataa reitti.
2. Olemassa olevat solmut ja reitin tiedot latautuvat muokkausnäkymään.
3. Muokkaa otsikkoa, kuvausta, julkaisupäivämäärää tai solmuja tarpeen mukaan (samat toiminnot kuin luomisessa).
4. Klikkaa Tallenna muutokset päivittääksesi reitin tiedot.

---

### Reitin poistaminen (pages/admin/delete-route.php)

1. Valitse poistettava reitti pudotusvalikosta.
2. Klikkaa Poista valittu reitti ja vahvista pyyntö.
   - **HUOM! Tätä toimintoa ei voi peruuttaa!**
3. Klikkaa Takaisin hallintapaneeliin palataksesi poistamatta.

![Reitin poistaminen](./user_guide_ss_9.png "Reitin poistaminen")

---

## Pelaajille

**HUOM! Pelaajille ei ole vielä erillistä versiota. Tällä hetkellä reittjä voi testata vain ylläpitäjät.**

### Reitin pelaaminen/Testaaminen (pages/game.php)

1. Avaa peli linkin kautta tai Hallintapaneelin "Reitin testaus" -osiosta.
2. Salli sijaintitietojen käyttö, kun selain pyytää — GPS on pakollinen.
3. Kartta latautuu ja näyttää reitin katkoviivana värillisine merkkeineen:
   - Vihreä = Aloitussolmu
   - Kulta = Maalisolmu
   - Punainen = Vierailemattomat solmut
   - Sininen piste = Nykyinen sijaintisi
   
   ![Reitin pelaaminen](./user_guide_ss_10.png "Reitin pelaaminen; Vihreä = Aloitussolmu, Kulta = Maalisolmu, Punainen = Vierailemattomat solmut, Sininen piste = Nykyinen sijaintisi")

4. Kävele kohti solmua. Kun olet 50 metrin sisällä, ponnahdusikkuna avautuu automaattisesti ja näyttää solmun sisällön.
   - **HUOM!** Jos ponnahdusikkuna ei avaudu, varmista että sijaintipalvelut ovat käytössä ja että olet riittävän lähellä solmua. Voit myös klikata solmun merkkiä kartalla avataksesi ponnahdusikkunan käsin.
5. Klikkaa "Merkitse vierailluksi" ponnahdusikkunassa kerätäksesi solmun ja ansaitaksesi tammenterhon.
   
    ![Solmun ponnahdusikkuna](./user_guide_ss_11.png "Solmun ponnahdusikkuna; Solmun sisältö ja Merkitse vierailluksi -painike")

6. Edistymispalkki alareunassa seuraa, kuinka monta solmua olet suorittanut.
7. Tietopaneeli (paina reitin nimen painiketta yläreunassa) näyttää tammenterhomääräsi ja etäisyyden seuraavaan solmuun.

   ![Edistymispalkki](./user_guide_ss_12.png "Edistymispalkki; Näyttää suoritettujen solmujen määrän")

   ![Tietopaneeli](./user_guide_ss_13.png "Tietopaneeli; Tammenterhot ja etäisyys seuraavaan solmuun")

8. Kun kaikki solmut on vierailtu, näytölle ilmestyy onnitteluviesti.
