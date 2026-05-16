<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class OfficerUiRenderTest extends TestCase
{
    public function test_officer_home_returns_200_with_alpine_root(): void
    {
        $response = $this->get(route('officer.home'));

        $response->assertStatus(200);
        $response->assertSee('x-data="officerApp"', false);
    }

    public function test_officer_login_returns_200_with_login_form(): void
    {
        $response = $this->get(route('officer.login'));

        $response->assertStatus(200);
        $response->assertSee('x-data="officerApp"', false);
        $response->assertSee('x-data="officerLogin"', false);
    }

    public function test_officer_assignments_returns_200_with_alpine_root(): void
    {
        $response = $this->get(route('officer.assignments'));

        $response->assertStatus(200);
        $response->assertSee('x-data="officerApp"', false);
        $response->assertSee('x-data="officerAssignments"', false);
    }

    public function test_officer_assignment_show_returns_200_with_alpine_root(): void
    {
        $response = $this->get(route('officer.assignments.show', ['id' => 'test-id']));

        $response->assertStatus(200);
        $response->assertSee('x-data="officerApp"', false);
        $response->assertSee('x-data="officerAssignmentShow"', false);
    }

    public function test_officer_checkin_returns_200_with_alpine_root(): void
    {
        $response = $this->get(route('officer.checkin', ['assignmentId' => 'test-id']));

        $response->assertStatus(200);
        $response->assertSee('x-data="officerApp"', false);
        $response->assertSee('x-data="checkinScreen"', false);
    }

    public function test_officer_bypass_returns_200_with_alpine_root(): void
    {
        $response = $this->get(route('officer.bypass'));

        $response->assertStatus(200);
        $response->assertSee('x-data="officerApp"', false);
        $response->assertSee('x-data="bypassScreen"', false);
    }

    public function test_officer_bypass_with_id_returns_200(): void
    {
        $response = $this->get(route('officer.bypass', ['bypassId' => 'test-bypass-id']));

        $response->assertStatus(200);
        $response->assertSee('x-data="officerApp"', false);
        $response->assertSee('x-data="bypassScreen"', false);
    }

    public function test_officer_history_returns_200_with_alpine_root(): void
    {
        $response = $this->get(route('officer.history'));

        $response->assertStatus(200);
        $response->assertSee('x-data="officerApp"', false);
        $response->assertSee('x-data="officerHistory"', false);
    }
}
