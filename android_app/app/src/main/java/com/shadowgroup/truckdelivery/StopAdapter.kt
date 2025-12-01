package com.shadowgroup.truckdelivery

import android.content.Intent
import android.net.Uri
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.shadowgroup.truckdelivery.databinding.ItemStopBinding
import android.location.Location

class StopAdapter(
    private var stops: List<Stop>,
    private val currentLocation: Location?
) : RecyclerView.Adapter<StopAdapter.StopViewHolder>() {

    class StopViewHolder(val binding: ItemStopBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): StopViewHolder {
        val binding = ItemStopBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return StopViewHolder(binding)
    }

    override fun onBindViewHolder(holder: StopViewHolder, position: Int) {
        val stop = stops[position]
        with(holder.binding) {
            tvOrder.text = stop.order_index.toString()
            tvName.text = stop.name
            tvOwner.text = "Dueño: ${stop.owner ?: "-"}"
            tvAddress.text = stop.address
            tvCity.text = stop.city ?: ""

            // Calculate Distance if location available
            if (currentLocation != null && stop.lat != null && stop.lng != null) {
                val results = FloatArray(1)
                Location.distanceBetween(
                    currentLocation.latitude, currentLocation.longitude,
                    stop.lat, stop.lng,
                    results
                )
                val distanceInMeters = results[0]
                tvDistance.text = if (distanceInMeters > 1000) {
                    String.format("%.1f km", distanceInMeters / 1000)
                } else {
                    String.format("%.0f m", distanceInMeters)
                }
            } else {
                tvDistance.text = ""
            }

            if (stop.lat != null && stop.lng != null) {
                btnMaps.visibility = View.VISIBLE
                btnWaze.visibility = View.VISIBLE

                btnMaps.setOnClickListener {
                    val uri = Uri.parse("geo:0,0?q=${stop.lat},${stop.lng}(${stop.name})")
                    val intent = Intent(Intent.ACTION_VIEW, uri)
                    intent.setPackage("com.google.android.apps.maps")
                    if (intent.resolveActivity(holder.itemView.context.packageManager) != null) {
                        holder.itemView.context.startActivity(intent)
                    } else {
                         // Fallback to browser
                         val browserIntent = Intent(Intent.ACTION_VIEW, Uri.parse("https://www.google.com/maps/search/?api=1&query=${stop.lat},${stop.lng}"))
                         holder.itemView.context.startActivity(browserIntent)
                    }
                }

                btnWaze.setOnClickListener {
                    val url = "https://waze.com/ul?ll=${stop.lat},${stop.lng}&navigate=yes"
                    val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
                    holder.itemView.context.startActivity(intent)
                }
            } else {
                btnMaps.visibility = View.GONE
                btnWaze.visibility = View.GONE
            }
        }
    }

    override fun getItemCount() = stops.size

    fun updateData(newStops: List<Stop>) {
        stops = newStops
        notifyDataSetChanged()
    }
}
