package com.shadowgroup.truckdelivery

import android.Manifest
import android.content.pm.PackageManager
import android.location.Location
import android.os.Bundle
import android.speech.tts.TextToSpeech
import android.text.Editable
import android.text.TextWatcher
import android.widget.Toast
import android.view.Menu
import android.view.MenuItem
import android.content.Intent
import android.widget.ArrayAdapter
import android.view.View
import android.widget.AdapterView
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.recyclerview.widget.LinearLayoutManager
import com.google.android.gms.location.LocationServices
import com.shadowgroup.truckdelivery.databinding.ActivityMainBinding
import org.osmdroid.config.Configuration
import org.osmdroid.tileprovider.tilesource.TileSourceFactory
import org.osmdroid.util.GeoPoint
import org.osmdroid.views.overlay.Marker
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response
import java.text.SimpleDateFormat
import java.util.*
import android.graphics.drawable.BitmapDrawable
import android.graphics.Bitmap
import android.graphics.Canvas
import android.graphics.Paint
import android.graphics.Color
import android.graphics.Typeface

class MainActivity : AppCompatActivity(), TextToSpeech.OnInitListener {

    private lateinit var binding: ActivityMainBinding
    private lateinit var tts: TextToSpeech
    private lateinit var adapter: StopAdapter
    private var allStops: List<Stop> = emptyList()
    private var currentLocation: Location? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // OSMDroid Config
        Configuration.getInstance().load(applicationContext, getPreferences(MODE_PRIVATE))

        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        // Setup Map
        binding.map.setTileSource(TileSourceFactory.MAPNIK)
        binding.map.setMultiTouchControls(true)
        binding.map.controller.setZoom(10.0)
        binding.map.controller.setCenter(GeoPoint(5.5353, -73.3678)) // Boyaca center

        // Setup List
        adapter = StopAdapter(emptyList(), null) { stop ->
            // On Item Click: Focus on Map
            focusOnMap(stop)
        }
        binding.recyclerView.layoutManager = LinearLayoutManager(this)
        binding.recyclerView.adapter = adapter

        // Init TTS
        tts = TextToSpeech(this, this)

        // Date Display
        displayDate()

        // Setup Day Spinner
        setupDaySpinner()

        // Permissions & Location
        checkPermissions()

        // Search Listener
        binding.etSearch.addTextChangedListener(object : TextWatcher {
            override fun afterTextChanged(s: Editable?) {
                filterList(s.toString())
            }
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {}
        })
    }

    private fun displayDate() {
        val sdf = SimpleDateFormat("dd/MM/yyyy", Locale("es", "ES"))
        val dateStr = sdf.format(Date())
        binding.tvDate.text = dateStr
    }

    private fun getDayName(): String {
        // Map Java Calendar days to API strings (Lunes, Martes...)
        val cal = Calendar.getInstance()
        return when (cal.get(Calendar.DAY_OF_WEEK)) {
            Calendar.MONDAY -> "Lunes"
            Calendar.TUESDAY -> "Martes"
            Calendar.WEDNESDAY -> "Miercoles"
            Calendar.THURSDAY -> "Jueves"
            Calendar.FRIDAY -> "Viernes"
            Calendar.SATURDAY -> "Sabado"
            else -> "Domingo" // No routes usually
        }
    }

    private fun setupDaySpinner() {
        val days = arrayOf("Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "Sabado")
        val adapter = ArrayAdapter(this, android.R.layout.simple_spinner_item, days)
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)
        binding.spinnerDays.adapter = adapter

        // Set selection to current day
        val currentDay = getDayName()
        val index = days.indexOf(currentDay)
        if (index >= 0) {
            binding.spinnerDays.setSelection(index)
        }

        // Explicitly fetch data for the initial selection because setting selection
        // programmatically might not trigger the listener if the index is 0.
        if (index >= 0) {
            fetchData(days[index])
        }

        binding.spinnerDays.onItemSelectedListener = object : AdapterView.OnItemSelectedListener {
            override fun onItemSelected(parent: AdapterView<*>?, view: View?, position: Int, id: Long) {
                val selectedDay = days[position]
                fetchData(selectedDay)
            }
            override fun onNothingSelected(parent: AdapterView<*>?) {}
        }
    }

    // Menu Logic
    override fun onCreateOptionsMenu(menu: Menu?): Boolean {
        menuInflater.inflate(R.menu.main_menu, menu)
        return true
    }

    override fun onOptionsItemSelected(item: MenuItem): Boolean {
        return when (item.itemId) {
            R.id.action_logout -> {
                logout()
                true
            }
            else -> super.onOptionsItemSelected(item)
        }
    }

    private fun logout() {
        val prefs = getSharedPreferences("TruckAppPrefs", MODE_PRIVATE)
        prefs.edit().clear().apply()
        startActivity(Intent(this, LoginActivity::class.java))
        finish()
    }

    private fun checkPermissions() {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            ActivityCompat.requestPermissions(this, arrayOf(Manifest.permission.ACCESS_FINE_LOCATION), 1)
        } else {
            getLocationAndFetchData()
        }
    }

    override fun onRequestPermissionsResult(requestCode: Int, permissions: Array<out String>, grantResults: IntArray) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        if (requestCode == 1 && grantResults.isNotEmpty() && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
            getLocationAndFetchData()
        } else {
            Toast.makeText(this, "Permiso de ubicación requerido para calcular distancias", Toast.LENGTH_SHORT).show()
            // We don't fetch here manually, the spinner listener will trigger it.
        }
    }

    private fun getLocationAndFetchData() {
        if (ActivityCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED) {
            val fusedLocationClient = LocationServices.getFusedLocationProviderClient(this)
            fusedLocationClient.lastLocation.addOnSuccessListener { location ->
                currentLocation = location
                if (location != null) {
                    val myLoc = GeoPoint(location.latitude, location.longitude)
                    val marker = Marker(binding.map)
                    marker.position = myLoc
                    marker.title = "Mi Ubicación"
                    marker.icon = ContextCompat.getDrawable(this, android.R.drawable.ic_menu_mylocation)
                    binding.map.overlays.add(marker)
                    binding.map.controller.animateTo(myLoc)
                }
                // Refresh UI to calculate distances now that we have location
                if (allStops.isNotEmpty()) {
                    updateUI()
                }
            }
        }
    }

    private fun fetchData(day: String) {
        val api = ApiClient.retrofit.create(ApiService::class.java)
        api.getRoutes(day).enqueue(object : Callback<List<Stop>> {
            override fun onResponse(call: Call<List<Stop>>, response: Response<List<Stop>>) {
                if (response.isSuccessful) {
                    allStops = response.body() ?: emptyList()
                    updateUI()
                    speakRouteInfo(day)
                }
            }

            override fun onFailure(call: Call<List<Stop>>, t: Throwable) {
                Toast.makeText(this@MainActivity, "Error cargando ruta: ${t.message}", Toast.LENGTH_SHORT).show()
            }
        })
    }

    private fun updateUI() {
        // Update List
        adapter = StopAdapter(allStops, currentLocation) { stop ->
            focusOnMap(stop)
        }
        binding.recyclerView.adapter = adapter

        // Update Map
        // Remove only markers that are Stops (not "Mi Ubicación")
        val overlaysToRemove = binding.map.overlays.filter {
            it is Marker && it.title != "Mi Ubicación"
        }
        binding.map.overlays.removeAll(overlaysToRemove)

        allStops.forEach { stop ->
            if (stop.lat != null && stop.lng != null) {
                val marker = Marker(binding.map)
                marker.position = GeoPoint(stop.lat, stop.lng)
                marker.title = "${stop.order_index}. ${stop.name}"
                marker.snippet = stop.address

                // Custom Icon with Number
                marker.icon = createNumberedMarkerBitmap(stop.order_index)

                binding.map.overlays.add(marker)
            }
        }
        binding.map.invalidate()
    }

    private fun focusOnMap(stop: Stop) {
        if (stop.lat != null && stop.lng != null) {
            val point = GeoPoint(stop.lat, stop.lng)
            binding.map.controller.animateTo(point)
            binding.map.controller.setZoom(18.0)

            // Find marker and show info window
            val marker = binding.map.overlays.filterIsInstance<Marker>().find {
                it.position.latitude == stop.lat && it.position.longitude == stop.lng
            }
            marker?.showInfoWindow()
        } else {
            Toast.makeText(this, "Este comercio no tiene ubicación.", Toast.LENGTH_SHORT).show()
        }
    }

    private fun createNumberedMarkerBitmap(number: Int): BitmapDrawable {
        val radius = 40f
        val diameter = (radius * 2).toInt()
        val bitmap = Bitmap.createBitmap(diameter, diameter, Bitmap.Config.ARGB_8888)
        val canvas = Canvas(bitmap)

        val paint = Paint()
        paint.isAntiAlias = true
        paint.color = Color.parseColor("#007bff") // Blue circle
        canvas.drawCircle(radius, radius, radius, paint)

        paint.color = Color.WHITE
        paint.textSize = 30f
        paint.typeface = Typeface.DEFAULT_BOLD
        paint.textAlign = Paint.Align.CENTER

        // Center text vertically
        val textY = radius - ((paint.descent() + paint.ascent()) / 2)
        canvas.drawText(number.toString(), radius, textY, paint)

        return BitmapDrawable(resources, bitmap)
    }

    private fun filterList(query: String) {
        val filtered = allStops.filter {
            it.name.contains(query, ignoreCase = true) ||
            (it.owner?.contains(query, ignoreCase = true) == true)
        }
        adapter.updateData(filtered)
    }

    override fun onInit(status: Int) {
        if (status == TextToSpeech.SUCCESS) {
            tts.language = Locale("es", "ES")
        }
    }

    private fun speakRouteInfo(day: String) {
        // Extract distinct cities
        val cities = allStops.mapNotNull { it.city }.distinct().joinToString(", ")

        if (cities.isNotEmpty()) {
            val text = "Buenos días, hoy es $day. La ruta de hoy incluye: $cities."
            tts.speak(text, TextToSpeech.QUEUE_FLUSH, null, null)
        }
    }

    override fun onDestroy() {
        if (::tts.isInitialized) {
            tts.stop()
            tts.shutdown()
        }
        super.onDestroy()
    }
}
