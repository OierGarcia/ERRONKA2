jQuery(function($){

  // PAGINAZIOAREN ALDAGAIAK
  var offset = 0;  // zein datutik hasi erakusten
  var limit  = 10; // pantailan aldi berean erakutsiko den errenkada kopurua

  // WORDPRESS-EN OINARRIZKO URLa LORTZEKO
  function base(){
    var u = window.location.href.split("/wordpress/");
    return u[0] + "/wordpress";
  }

  // PHP SCRIPT-AREN URLa LORTZEKO
  function url(){
    return base() + "/personal-php/php-json.php"; // /personal-php/php-json.php gehitu oinarriari
  }

  // TAULA
  function renderTable(rows){
    // rows ez badago edo hutsik badago, mezua erakutsi eta irten
    if(!rows || !rows.length){
      $('#esp-plugin-content').html('<p>Ez dago daturik.</p>');
      return;
    }
    // Taularen hasierako HTML egitura (goiburua barne)
    var t = '<table border=1 cellpadding=6 style="width:100%"><thead><tr>'+
            '<th>data</th><th>tenperatura</th><th>hezetasuna</th><th>soinua</th><th>mugimendua</th></tr></thead><tbody>';
    // Datu errenkada bakoitzerako
    rows.forEach(function(r){
      var mov = (r.pir == 1) ? 'BAI' : 'EZ'; // PIR sentsorearen balioa: 1 = BAI (mugimendua), 0 = EZ
      // Zutabe bakoitzean dagokion datua sartu
      t += '<tr>'+
           '<td>'+r.ts+'</td>'+
           '<td>'+r.temperatura+'</td>'+
           '<td>'+r.humedad+'</td>'+
           '<td>'+r.sonido+'</td>'+
           '<td>'+mov+'</td>'+
           '</tr>';
    });
    t += '</tbody></table>';// Taula itxi
    $('#esp-plugin-content').html(t); // Sortutako taula HTMLa #esp-plugin-content div-ean jarri
  }

  // PAGINAZIOA
  function renderPager(total){
    var page  = Math.floor(offset/limit) + 1; // Uneko orria kalkulatu (offset eta limit erabilita)
    var pages = Math.max(1, Math.ceil(total/limit)); // Orri guztien kopurua kalkulatu (gutxienez 1)

    // Testua eta botoiak sortu
    var html = 'Orrialdea '+page+'/'+pages+' (guztira '+total+') ';  
    html += '<button id="esp-prev" '+(offset<=0?'disabled':'')+'>Aurrekoa</button> '; // "Aurrekoa" botoia: lehen orrian bagaude, desgaitua
    html += '<button id="esp-next" '+((offset+limit)>=total?'disabled':'')+'>Hurrengoa</button>'; // "Hurrengoa" botoia: azkeneko orria bada, desgaitua

    $('#esp-pagination').html(html); // HTML hori #esp-pagination div-ean jarri

    // Botoien kliken kudeaketa
    $('#esp-prev').off('click').on('click', function(){
      if(offset > 0){  
        offset = Math.max(0, offset - limit); // offset murriztu, baina inoiz ez 0tik behera
        load(); // datuak berriro kargatu orri berriarekin
      }
    });

    $('#esp-next').off('click').on('click', function(){
      if(offset + limit < total){
        offset += limit; // offset handitu hurrengo orrira joateko       
        load(); // datuak berriro kargatu
      }
    });
  }

  // GRAFIKOA(Chart.js)
  var chart = null; //grafikoa gorde

  
  function ensureChart(cb){ // Chart.js kargatuta dagoela ziurtatu
    if(typeof Chart !== 'undefined') return cb(); // Chart globala badago, berehala exekutatu callback-a

    var s = document.createElement('script'); // Bestela, script etiketa sortu eta CDN-etik kargatu
    s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    s.onload = cb; // Script-a kargatzean callback exekutatu

    document.head.appendChild(s); // Script-a <head>-en txertatu
  }

  // grafikoa erakutsi (tenperatura eta hezetasuna)
  function renderChart(rows){
   
    var asc   = rows.slice().reverse(); // Datuen ordena alderantzikatu, zaharrenetik berrienera joateko
    
    var labels= asc.map(r => r.ts); // X ardatzeko etiketak (timestamp)
    var temps = asc.map(r => Number(r.temperatura)); // Tenperatura balioak
    var hums  = asc.map(r => Number(r.humedad)); // Hezetasun balioak

    // Ziurtatu Chart.js kargatuta dagoela
    ensureChart(function(){
      var canvas = document.getElementById('esp-chart');
      // canvas aurkitzen ez bada, ezer ez egin
      if(!canvas) return;
      var ctx = canvas.getContext('2d');

      // Grafikoa dagoeneko sortuta badago, datuak eguneratu soilik
      if(chart){
        chart.data.labels = labels;
        chart.data.datasets[0].data = temps;
        chart.data.datasets[1].data = hums;
        chart.update();
        return;
      }

      // Bestela, grafiko berria sortu
      chart = new Chart(ctx, {
        type: 'line', // lerro-grafikoa
        data: {
          labels: labels,
          datasets: [
            {
              label:'Tenp (°C)',
              data:temps,
              fill:false,
              tension:0.2,
              yAxisID:'y'  // ezkerreko Y ardatza
            },
            {
              label:'Hez (%)',
              data:hums,
              fill:false,
              tension:0.2,
              yAxisID:'y1' // eskuineko Y ardatza
            }
          ]
        },
        options:{
          responsive:true,
          scales:{
            // Ezkerreko Y ardatza
            y:{
              type:'linear',
              position:'left'
            },
            // Eskuineko Y ardatza (hezetasuna), sare-marra gabe
            y1:{
              type:'linear',
              position:'right',
              grid:{ drawOnChartArea:false }
            }
          }
        }
      });
    });
  }

  // DATUAK KARGATZEKO FUNTZIO NAGUSIA
  function load(){
    // Lehenik, kargatzen ari dela adierazi
    $('#esp-plugin-content').html('Kargatzen...');

    // Data iragazkien balioak hartu
    var df = $('#esp-date-from').val() || '';
    var dt = $('#esp-date-to').val()   || '';

    // Data formatua YYYY-MM-DD bada (10 karaktere), ordua gehitu
    if(df && df.length === 10) df += ' 00:00:00';   // egunaren hasiera
    if(dt && dt.length === 10) dt += ' 23:59:59';   // egunaren amaiera

    // AJAX eskaerarako parametroak prestatu
    var params = '?limit='+limit+'&offset='+offset;
    // Data hasiera eta amaiera balioak badaude, URL-ean gehitu
    if(df) params += '&date_from='+encodeURIComponent(df);
    if(dt) params += '&date_to='+encodeURIComponent(dt);

    // PHP script-era GET eskaera JSON formatuan
    $.getJSON(url() + params, function(res){
      // Erantzuna txarra bada edo res.ok faltsua bada, errorea erakutsi
      if(!res || !res.ok){
        $('#esp-plugin-content').html('<p>Error servidor</p>');
        return;
      }
      // Erantzunetik datu-errenkadak eta guztira kopurua atera
      var rows  = res.rows  || [];
      var total = res.total || 0;
      // Taula, paginazioa eta grafikoa eguneratu
      renderTable(rows);
      renderPager(total);
      renderChart(rows);
    }).fail(function(){
      // AJAX huts egiten badu (konexio arazoa, etab.), errore mezua
      $('#esp-plugin-content').html('<p>Error AJAX</p>');
    });
  }

  // BOTOIEN EVENTUAK

  // "Berritu" botoia sakatzean:
  $('#esp-refresh').on('click', function(){
    // Lehen orrira bueltatu
    offset = 0;
    // Datuak berriro kargatu (uneko iragazkiekin)
    load();
  });

  // "Reset" botoia sakatzean:
  $('#esp-reset').on('click', function(){
    // Data iragazkiak garbitu
    $('#esp-date-from').val('');
    $('#esp-date-to').val('');
    // Lehen orrira bueltatu
    offset = 0;
    // Datu guztiak berriro kargatu (iragazkirik gabe)
    load();
  });

  // ORRIA KARGATZEAN LEHENENGO DEIA
  // Script-a hasieratzen denean, berehala datuak kargatu
  load();

});
