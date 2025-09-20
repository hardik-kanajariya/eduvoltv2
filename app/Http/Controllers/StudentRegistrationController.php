<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StudentRegistrationRequest;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentRegistrationController extends Controller
{
    /**
     * Show the multi-step registration form.
     */
    public function index(Request $request): View
    {
        Gate::authorize('create', Student::class);

        $step = $request->get('step', 1);
        $step = max(1, min(4, (int) $step)); // Ensure step is between 1-4

        return view('students.registration.index', [
            'step' => $step,
            'totalSteps' => 4,
        ]);
    }

    /**
     * Show step 1: Basic Information.
     */
    public function step1(Request $request): View
    {
        Gate::authorize('create', Student::class);

        return view('students.registration.step1', [
            'step' => 1,
            'totalSteps' => 4,
            'data' => session('registration_data', []),
        ]);
    }

    /**
     * Process step 1 and move to step 2.
     */
    public function processStep1(Request $request): RedirectResponse
    {
        Gate::authorize('create', Student::class);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:students,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female,other'],
            'address' => ['required', 'string', 'max:500'],
            'blood_group' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
        ]);

        // Store step 1 data in session
        $registrationData = session('registration_data', []);
        $registrationData['step1'] = $validated;
        session(['registration_data' => $registrationData]);

        return redirect()->route('students.registration.step2');
    }

    /**
     * Show step 2: Academic Information.
     */
    public function step2(Request $request): View|RedirectResponse
    {
        Gate::authorize('create', Student::class);

        // Ensure step 1 is completed
        if (!session('registration_data.step1')) {
            return redirect()->route('students.registration.step1')
                           ->with('error', 'Please complete step 1 first.');
        }

        return view('students.registration.step2', [
            'step' => 2,
            'totalSteps' => 4,
            'data' => session('registration_data', []),
        ]);
    }

    /**
     * Process step 2 and move to step 3.
     */
    public function processStep2(Request $request): RedirectResponse
    {
        Gate::authorize('create', Student::class);

        $validated = $request->validate([
            'admission_number' => ['required', 'string', 'max:20', 'unique:students,admission_number'],
            'grade_level' => ['required', 'string', 'max:10'],
            'class_section' => ['nullable', 'string', 'max:10'],
            'enrollment_date' => ['required', 'date'],
            'academic_year' => ['required', 'string', 'max:10'],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        // Store step 2 data in session
        $registrationData = session('registration_data', []);
        $registrationData['step2'] = $validated;
        session(['registration_data' => $registrationData]);

        return redirect()->route('students.registration.step3');
    }

    /**
     * Show step 3: Parent/Guardian and Emergency Contact Information.
     */
    public function step3(Request $request): View|RedirectResponse
    {
        Gate::authorize('create', Student::class);

        // Ensure previous steps are completed
        if (!session('registration_data.step1') || !session('registration_data.step2')) {
            return redirect()->route('students.registration.step1')
                           ->with('error', 'Please complete all previous steps first.');
        }

        return view('students.registration.step3', [
            'step' => 3,
            'totalSteps' => 4,
            'data' => session('registration_data', []),
        ]);
    }

    /**
     * Process step 3 and move to step 4.
     */
    public function processStep3(Request $request): RedirectResponse
    {
        Gate::authorize('create', Student::class);

        $validated = $request->validate([
            // Parent/Guardian Information
            'parent_name' => ['required', 'string', 'max:255'],
            'parent_phone' => ['required', 'string', 'max:20'],
            'parent_email' => ['required', 'email', 'max:255'],
            'parent_relationship' => ['required', 'in:father,mother,guardian,other'],

            // Emergency Contact
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:20'],
            'emergency_contact_relationship' => ['required', 'string', 'max:50'],
        ]);

        // Store step 3 data in session
        $registrationData = session('registration_data', []);
        $registrationData['step3'] = $validated;
        session(['registration_data' => $registrationData]);

        return redirect()->route('students.registration.step4');
    }

    /**
     * Show step 4: Medical Information and Documents.
     */
    public function step4(Request $request): View|RedirectResponse
    {
        Gate::authorize('create', Student::class);

        // Ensure previous steps are completed
        if (!session('registration_data.step1') || !session('registration_data.step2') || !session('registration_data.step3')) {
            return redirect()->route('students.registration.step1')
                           ->with('error', 'Please complete all previous steps first.');
        }

        return view('students.registration.step4', [
            'step' => 4,
            'totalSteps' => 4,
            'data' => session('registration_data', []),
        ]);
    }

    /**
     * Process step 4 and complete registration.
     */
    public function processStep4(StudentRegistrationRequest $request): RedirectResponse
    {
        Gate::authorize('create', Student::class);

        try {
            DB::beginTransaction();

            // Get all session data
            $registrationData = session('registration_data', []);

            // Validate we have all steps
            if (!isset($registrationData['step1'], $registrationData['step2'], $registrationData['step3'])) {
                throw new \Exception('Registration data is incomplete. Please start over.');
            }

            // Validate step 4 data
            $step4Data = $request->validate([
                // Medical Information
                'medical_conditions' => ['nullable', 'array'],
                'medical_conditions.*' => ['string', 'max:255'],
                'allergies' => ['nullable', 'array'],
                'allergies.*' => ['string', 'max:255'],
                'medications' => ['nullable', 'array'],
                'medications.*' => ['string', 'max:255'],
                'emergency_medical_info' => ['nullable', 'string', 'max:1000'],

                // Document uploads
                'photo' => ['nullable', 'image', 'max:2048'], // 2MB max
                'documents' => ['nullable', 'array'],
                'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // 5MB max
            ]);

            // Combine all registration data
            $allData = array_merge(
                $registrationData['step1'],
                $registrationData['step2'],
                $registrationData['step3'],
                $step4Data
            );

            // Handle file uploads
            if ($request->hasFile('photo')) {
                $allData['photo'] = $request->file('photo')->store('students/photos', 'public');
            }

            if ($request->hasFile('documents')) {
                $documentPaths = [];
                foreach ($request->file('documents') as $document) {
                    $documentPaths[] = $document->store('students/documents', 'public');
                }
                $allData['documents'] = json_encode($documentPaths);
            }

            // Convert medical arrays to JSON
            $allData['medical_conditions'] = json_encode($allData['medical_conditions'] ?? []);
            $allData['allergies'] = json_encode($allData['allergies'] ?? []);
            $allData['medications'] = json_encode($allData['medications'] ?? []);

            // Create student record
            $student = Student::create($allData);

            DB::commit();

            // Clear registration data from session
            session()->forget('registration_data');

            return redirect()->route('students.show', $student)
                           ->with('success', 'Student registration completed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Show registration review page with all collected data.
     */
    public function review(): View|RedirectResponse
    {
        Gate::authorize('create', Student::class);

        $registrationData = session('registration_data', []);

        // Ensure all steps are completed
        if (!isset($registrationData['step1'], $registrationData['step2'], $registrationData['step3'])) {
            return redirect()->route('students.registration.step1')
                           ->with('error', 'Please complete all registration steps first.');
        }

        return view('students.registration.review', [
            'data' => $registrationData,
        ]);
    }

    /**
     * Clear registration data and start over.
     */
    public function restart(): RedirectResponse
    {
        session()->forget('registration_data');

        return redirect()->route('students.registration.step1')
                       ->with('info', 'Registration data cleared. You can start the registration process again.');
    }
}
