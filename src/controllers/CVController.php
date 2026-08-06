<?php
class CVController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $uid = userId();
        $cv  = Database::fetch('SELECT * FROM cv_profiles WHERE user_id = ?', [$uid]);
        // Decode JSON sections
        if ($cv) {
            $cv['experience'] = json_decode($cv['experience'], true) ?: [];
            $cv['education']  = json_decode($cv['education'],  true) ?: [];
            $cv['skills']     = json_decode($cv['skills'],     true) ?: [];
            $cv['languages']  = json_decode($cv['languages'],  true) ?: [];
        }
        view('pages/cv', ['cv' => $cv, 'user' => Auth::user()]);
    }

    public static function save(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();

        $experience = json_decode(input('experience', '[]'), true) ?: [];
        $education  = json_decode(input('education',  '[]'), true) ?: [];
        $skills     = is_array(input('skills'))
                        ? array_values(array_filter(array_map('trim', (array) input('skills'))))
                        : array_values(array_filter(array_map('trim', explode(',', input('skills', '')))));
        $languages  = is_array(input('languages'))
                        ? array_values(array_filter(array_map('trim', (array) input('languages'))))
                        : array_values(array_filter(array_map('trim', explode(',', input('languages', '')))));

        $data = [
            'full_name'      => sanitize(input('full_name', '')),
            'job_title'      => sanitize(input('job_title', '')),
            'email'          => sanitize(input('email', '')),
            'phone'          => sanitize(input('phone', '')),
            'address'        => sanitize(input('address', '')),
            'website'        => sanitize(input('website', '')),
            'linkedin'       => sanitize(input('linkedin', '')),
            'summary'        => sanitize(input('summary', '')),
            'experience'     => json_encode($experience),
            'education'      => json_encode($education),
            'skills'         => json_encode($skills),
            'languages'      => json_encode($languages),
            'template_color' => sanitize(input('template_color', '#0078D4')),
        ];

        $existing = Database::fetch('SELECT id FROM cv_profiles WHERE user_id = ?', [$uid]);

        if ($existing) {
            Database::execute(
                'UPDATE cv_profiles SET full_name=?,job_title=?,email=?,phone=?,address=?,
                 website=?,linkedin=?,summary=?,experience=?,education=?,skills=?,languages=?,
                 template_color=?,updated_at=datetime(\'now\') WHERE user_id=?',
                [...array_values($data), $uid]
            );
        } else {
            Database::execute(
                'INSERT INTO cv_profiles (user_id,full_name,job_title,email,phone,address,
                 website,linkedin,summary,experience,education,skills,languages,template_color)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$uid, ...array_values($data)]
            );
        }

        flash('success', 'CV saved successfully.');
        redirect('/cv');
    }
}
