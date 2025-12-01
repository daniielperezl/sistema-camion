package com.shadowgroup.truckdelivery

data class LoginResponse(
    val success: Boolean,
    val message: String?,
    val user: User?
)

data class User(
    val id: Int,
    val username: String
)

data class Stop(
    val id: Int,
    val route_id: Int,
    val city: String?,
    val name: String,
    val owner: String?,
    val address: String,
    val phone: String?,
    val notes: String?,
    val lat: Double?,
    val lng: Double?,
    val order_index: Int
)

data class LoginRequest(
    val username: String,
    val password: String
)
