<?php

/*
|--------------------------------------------------------------------------
| FMH Animal Clinic: information shown on the landing page (home page)
|--------------------------------------------------------------------------
| Edit the text here; no other file needs to change.
| The clinic HOURS are not here: they come from Super Admin -> Settings -> Clinic Hours,
| so the home page always shows the same hours that the booking uses.
|
| Service photos: put a picture in public/image/services/ named after the "slug",
| e.g. public/image/services/xray.jpg (.jpg, .png or .webp). Without a photo, the icon is shown.
| Branch photos: "photo" is a file in public/image/ (later you can use a real photo of each branch).
| "book" = the name of the service in the system that can be booked online (null = visit or call the clinic).
*/

return [
    'branches' => [
        [
            'name' => 'Main Branch',
            'address' => '11 Ruby Road cor. Aguirre, Pilar Village, Las Piñas City',
            'phones' => ['(02) 8806-5772', '0932-314-5969'],
            'online_booking' => true,   // the system's appointments are for this branch
            'photo' => 'image/home.jpg',
        ],
        [
            'name' => 'Molino Branch',
            'address' => 'BM7 Bldg. Stall B, Molino II, Molino Rd., Bacoor, Cavite',
            'phones' => ['(046) 477-2846', '0932-557-8921'],
            'online_booking' => false,
            'photo' => 'image/services.jpg',
        ],
        [
            'name' => "Queen's Row Branch",
            'address' => "Blk 22, Lot 23, Queens Main Blvd., Queen's Row Central, Bacoor, Cavite",
            'phones' => ['(046) 435-9617', '0933-927-2498'],
            'online_booking' => false,
            'photo' => 'image/contact.jpg',
        ],
    ],

    'inquiries' => ['0932-314-5969', '(02) 8806-5772'],

    // The clinic's email for the Contact section (null = not shown). Write the real email of the clinic here.
    'email' => null,

    // url = null shows the name without a link
    'socials' => [
        ['type' => 'facebook', 'label' => 'FMH Animal Clinic', 'url' => null],
        ['type' => 'instagram', 'label' => '@fmh.animalclinic', 'url' => 'https://www.instagram.com/fmh.animalclinic'],
        ['type' => 'tiktok', 'label' => '@fmhanimalclinic', 'url' => 'https://www.tiktok.com/@fmhanimalclinic'],
    ],

    'services' => [
        ['slug' => 'consultation', 'name' => 'Consultation', 'icon' => 'stethoscope', 'book' => 'Consultation',
            'text' => "A complete check-up by our veterinarian. We look at your pet's health, answer your questions and suggest the next steps."],
        ['slug' => 'laboratory', 'name' => 'Laboratory Tests', 'icon' => 'flask', 'book' => null,
            'text' => "Tests on blood, urine or stool samples that help the vet find out what is making your pet sick."],
        ['slug' => 'cbc', 'name' => 'CBC and Blood Chemistry', 'icon' => 'droplet', 'book' => null,
            'text' => "Blood tests that check your pet's blood cells and how organs like the liver and kidneys are working."],
        ['slug' => 'ultrasound', 'name' => 'Ultrasonography', 'icon' => 'activity', 'book' => null,
            'text' => "A safe and painless scan that lets the vet see inside your pet's belly, for example to check pregnancy or the organs."],
        ['slug' => 'xray', 'name' => 'X-ray', 'icon' => 'bone', 'book' => null,
            'text' => "Pictures of your pet's bones, chest and belly to find fractures, swallowed objects and other problems."],
        ['slug' => 'treatment', 'name' => 'Treatment', 'icon' => 'plus', 'book' => 'General Treatment',
            'text' => "Care for illness or injury, with the medicines and procedures prescribed by the veterinarian."],
        ['slug' => 'confinement', 'name' => 'Confinement', 'icon' => 'bed', 'book' => null,
            'text' => "Your pet stays at the clinic so our team can watch and treat them while they recover."],
        ['slug' => 'surgery', 'name' => 'Surgery', 'icon' => 'heart-pulse', 'book' => null,
            'text' => "Operations done by our veterinarians, with anesthesia, monitoring and after-care."],
        ['slug' => 'dental', 'name' => 'Dental Prophylaxis', 'icon' => 'smile', 'book' => null,
            'text' => "Professional teeth cleaning that removes tartar and keeps your pet's mouth and breath healthy."],
        ['slug' => 'vaccination', 'name' => 'Vaccination', 'icon' => 'syringe', 'book' => 'Vaccination',
            'text' => "Vaccines that protect your pet from common and dangerous diseases like parvo, distemper and rabies."],
        ['slug' => 'deworming', 'name' => 'Deworming', 'icon' => 'bug', 'book' => 'Deworming',
            'text' => "Medicine that removes intestinal worms and keeps your pet (and your family) healthy."],
        ['slug' => 'grooming', 'name' => 'Grooming', 'icon' => 'scissors', 'book' => 'Grooming',
            'text' => "Bath, haircut, nail trimming and ear cleaning so your pet looks and feels their best."],
        ['slug' => 'health-certificate', 'name' => 'Health Certificate', 'icon' => 'file-check', 'book' => null,
            'text' => "An official certificate that your pet is healthy, often needed for travel or shipping."],
        ['slug' => 'pharmacy', 'name' => 'Pharmacy', 'icon' => 'pill', 'book' => null,
            'text' => "Medicines, vitamins and pet care products, prescribed or recommended by our veterinarians."],
        ['slug' => 'spay-neuter', 'name' => 'Spay and Neuter', 'icon' => 'shield-plus', 'book' => null,
            'text' => "A routine surgery that prevents unwanted litters and helps your pet live a longer, healthier life."],
    ],

    // Line icons (Lucide, ISC license) for the service cards
    'icons' => [
        'stethoscope' => '<path d="M11 2v2"/><path d="M5 2v2"/><path d="M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1"/><path d="M8 15a6 6 0 0 0 12 0v-3"/><circle cx="20" cy="10" r="2"/>',
        'flask' => '<path d="M14 2v6a2 2 0 0 0 .245.96l5.51 10.08A2 2 0 0 1 18 22H6a2 2 0 0 1-1.755-2.96l5.51-10.08A2 2 0 0 0 10 8V2"/><path d="M6.453 15h11.094"/><path d="M8.5 2h7"/>',
        'droplet' => '<path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/>',
        'activity' => '<path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/>',
        'bone' => '<path d="M17 10c.7-.7 1.69 0 2.5 0a2.5 2.5 0 1 0 0-5 .5.5 0 0 1-.5-.5 2.5 2.5 0 1 0-5 0c0 .81.7 1.8 0 2.5l-7 7c-.7.7-1.69 0-2.5 0a2.5 2.5 0 0 0 0 5c.28 0 .5.22.5.5a2.5 2.5 0 1 0 5 0c0-.81-.7-1.8 0-2.5Z"/>',
        'plus' => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M8 12h8"/><path d="M12 8v8"/>',
        'bed' => '<path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/>',
        'heart-pulse' => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="M3.22 12H9.5l.5-1 2 4.5 2-7 1.5 3.5h5.27"/>',
        'smile' => '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/>',
        'syringe' => '<path d="m18 2 4 4"/><path d="m17 7 3-3"/><path d="M19 9 8.7 19.3c-1 1-2.5 1-3.4 0l-.6-.6c-1-1-1-2.5 0-3.4L15 5"/><path d="m9 11 4 4"/><path d="m5 19-3 3"/><path d="m14 4 6 6"/>',
        'bug' => '<path d="m8 2 1.88 1.88"/><path d="M14.12 3.88 16 2"/><path d="M9 7.13v-1a3.003 3.003 0 1 1 6 0v1"/><path d="M12 20c-3.3 0-6-2.7-6-6v-3a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v3c0 3.3-2.7 6-6 6"/><path d="M12 20v-9"/><path d="M6.53 9C4.6 8.8 3 7.1 3 5"/><path d="M6 13H2"/><path d="M3 21c0-2.1 1.7-3.9 3.8-4"/><path d="M20.97 5c0 2.1-1.6 3.8-3.5 4"/><path d="M22 13h-4"/><path d="M17.2 17c2.1.1 3.8 1.9 3.8 4"/>',
        'scissors' => '<circle cx="6" cy="6" r="3"/><path d="M8.12 8.12 12 12"/><path d="M20 4 8.12 15.88"/><circle cx="6" cy="18" r="3"/><path d="M14.8 14.8 20 20"/>',
        'file-check' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="m9 15 2 2 4-4"/>',
        'pill' => '<path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/>',
        'shield-plus' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="M9 12h6"/><path d="M12 9v6"/>',
        'phone' => '<path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"/>',
        'map-pin' => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
        'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'instagram' => '<rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>',
        'tiktok' => '<path d="M16 3a5 5 0 0 0 5 5"/><path d="M16 3v11a5 5 0 1 1-5-5"/>',
    ],
];
