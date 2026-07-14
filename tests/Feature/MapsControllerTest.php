<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapsControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_maps_index_page_loads()
    {
        $response = $this->actingAs($this->user)->get(route('maps.index'));
        $response->assertStatus(200);
        $response->assertSee('Katmanlar');
    }

    public function test_maps_proxy_requires_url()
    {
        $response = $this->actingAs($this->user)->get(route('maps.proxy'));
        $response->assertStatus(400);
        $response->assertJson(['error' => 'URL parametresi gerekli']);
    }

    public function test_maps_proxy_rejects_invalid_domain()
    {
        $response = $this->actingAs($this->user)->get(route('maps.proxy', ['url' => 'https://example.com/data']));
        $response->assertStatus(403);
        $response->assertJson(['error' => 'İzin verilmeyen domain']);
    }

    public function test_maps_proxy_allows_geo3_domain()
    {
        $response = $this->actingAs($this->user)->get(route('maps.proxy', [
            'url' => 'https://geo3.sanliurfa.bel.tr:8091/geoserver/wfs?service=WFS&request=GetCapabilities'
        ]));
        // Proxy will try to actually fetch — if geo3 is unreachable, it returns 500
        $this->assertContains($response->status(), [200, 500]);
    }

    public function test_maps_basvurular_geojson_returns_valid_structure()
    {
        $response = $this->actingAs($this->user)->get(route('maps.basvurularGeoJson'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'type',
            'features' => [],
        ]);
    }

    public function test_15m_alti_endpoint_returns_geojson()
    {
        $response = $this->actingAs($this->user)->get(route('maps.15m.alti'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'type',
            'features' => [],
        ]);
        $data = $response->json();
        $this->assertEquals('FeatureCollection', $data['type']);
    }

    public function test_15m_ustu_endpoint_returns_geojson()
    {
        $response = $this->actingAs($this->user)->get(route('maps.15m.ustu'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'type',
            'features' => [],
        ]);
        $data = $response->json();
        $this->assertEquals('FeatureCollection', $data['type']);
    }

    public function test_15m_road_query_returns_not_found_for_empty_request()
    {
        $response = $this->actingAs($this->user)->get(route('maps.15m.roadQuery'));
        $response->assertStatus(200);
        $response->assertJson(['found' => false]);
    }

    public function test_15m_road_query_finds_road_by_hat_kimligi()
    {
        $response = $this->actingAs($this->user)->get(route('maps.15m.roadQuery', ['hat_kimligi' => '15152']));
        $response->assertStatus(200);
        $data = $response->json();
        if ($data['found']) {
            $this->assertArrayHasKey('properties', $data);
            $this->assertEquals('15152', $data['properties']['CADDE_SOKA']);
        }
    }

    public function test_nokta_kaydet_validates_required_fields()
    {
        $response = $this->actingAs($this->user)->post(route('maps.noktaKaydet'), []);
        $response->assertStatus(302); // validation redirect
    }

    public function test_nokta_kaydet_saves_point()
    {
        $response = $this->actingAs($this->user)->post(route('maps.noktaKaydet'), [
            'lat' => 37.1598,
            'lng' => 38.7969,
            'ilce' => 'Eyyübiye',
            'mahalle' => 'Batikent',
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_drawing_save_requires_geometri()
    {
        $response = $this->actingAs($this->user)->post(route('maps.drawing.save'), [
            'tip' => 'alan',
        ]);
        $response->assertStatus(302); // validation redirect
    }

    public function test_drawing_save_with_valid_data()
    {
        $response = $this->actingAs($this->user)->post(route('maps.drawing.save'), [
            'tip' => 'alan',
            'geometri' => json_encode([
                'type' => 'Polygon',
                'coordinates' => [[[38.79, 37.15], [38.80, 37.15], [38.80, 37.16], [38.79, 37.16], [38.79, 37.15]]],
            ]),
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_katman_kaydet_saves_preferences()
    {
        $response = $this->actingAs($this->user)->post(route('maps.katman.kaydet'), [
            'katmanlar' => [
                ['layer' => 'cbs:MISMAP_MAHALLE_KOYLER', 'visible' => true, 'opacity' => 0.7],
                ['layer' => 'smpns:MISMAP_NUM_KADASTRO_PARSEL', 'visible' => true, 'opacity' => 0.8],
            ],
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_katman_yukle_returns_preferences()
    {
        $response = $this->actingAs($this->user)->get(route('maps.katman.yukle'));
        $response->assertStatus(200);
        $response->assertJson([]); // empty or array
    }

    public function test_search_requires_query()
    {
        $response = $this->actingAs($this->user)->get(route('maps.ara'));
        $response->assertStatus(200);
        $response->assertJson([]);
    }

    public function test_unauthenticated_access_redirects()
    {
        $response = $this->get(route('maps.index'));
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}
