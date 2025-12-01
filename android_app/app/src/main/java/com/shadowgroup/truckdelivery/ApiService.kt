package com.shadowgroup.truckdelivery

import retrofit2.Call
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Query

interface ApiService {
    @POST("login.php")
    fun login(@Body request: LoginRequest): Call<LoginResponse>

    @GET("routes.php")
    fun getRoutes(@Query("day") day: String): Call<List<Stop>>
}
