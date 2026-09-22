<?php
/**
 * includes/team-data.php
 * ---------------------------------------------------------------------
 * Single source of truth for The Atelier Noir roster. Used by:
 *   - about.php            (team grid, hover skill-chips, "View More")
 *   - team-member.php      (full profile page, ?slug=...)
 *
 * To add a photo: drop the file in /assets/images/team/ and set
 * 'image' to that path. Leave it as '' and the card/profile will fall
 * back to the gold initials monogram automatically — nothing breaks.
 * ---------------------------------------------------------------------
 */

return [

    'founders' => [
        [
            'slug'     => 'elizabeth-torres',
            'name'     => 'Elizabeth Jeanel Torres',
            'role'     => 'Founder &amp; Owner &middot; UI/UX Consultant',
            'initials' => 'EJT',
            'image'    => 'https://images.pexels.com/photos/5253698/pexels-photo-5253698.jpeg?cs=tinysrgb&dpr=1&w=500', // e.g. '/assets/images/team/elizabeth.jpg'
            'bio'      => 'Leading The Atelier Noir with a vision centered on refined fashion and thoughtful experiences. Elizabeth oversees the brand\'s direction while bringing a strong perspective in user experience and interface design.',
            'bio_full' => 'Elizabeth founded The Atelier Noir on the belief that fashion and digital experience should be shaped by the same discipline: intention. She sets the brand\'s creative direction and works closely across every team to make sure each decision — from a garment\'s silhouette to the way a page transitions — reflects the same standard of restraint and elegance. Her background in UI/UX consulting means the brand\'s digital storefront is never an afterthought to the collection; it is designed with it, from the same brief.',
            'skills'   => ['Brand Direction', 'UI/UX Design', 'Creative Strategy', 'Client Experience'],
        ],
        [
            'slug'     => 'prince-guevara',
            'name'     => 'Prince Edward Guevara',
            'role'     => 'Founder &amp; Co-Owner &middot; Full-Stack Developer',
            'initials' => 'PEG',
            'image'    => 'https://scontent.fmnl45-2.fna.fbcdn.net/v/t39.30808-6/756440548_1604615271371396_2390313139881660168_n.jpg?stp=dst-jpg_tt6&cstp=mx1536x1546&ctp=s1536x1546&_nc_cat=104&_nc_map=urlgen_bucketless&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeGhZf8d8HdJ6N32BGOdxPtOwLxDLxkIS_fAvEMvGQhL9x9JlGA_95OhqfEA_wk2odAM1-HQhIJvEBB7uzAwD-kD&_nc_ohc=Bpy1XSJrEeIQ7kNvwGtyZKZ&_nc_oc=AdqUCUHnQ6THemnmjO_gp3WbBisaTuwmkUqZ3xPiH3CGK88ictFKeNb3rI-ZtyyTew9vFlRAVT--e27j_A5DTNUN&_nc_zt=23&_nc_ht=scontent.fmnl45-2.fna&_nc_gid=6l1xAnHeRRNDQYY1eLEQxw&_nc_ss=7b2a8&oh=00_AQHjPdWc4lJZCR99reLTYefTqlUtdpaAHse1nSu9vbuYBw&oe=6A84E35E',
            'bio'      => 'Bringing technology and innovation into the foundation of The Atelier Noir. Prince develops and maintains the digital systems behind the brand, ensuring that its online experience remains functional, modern, and seamless.',
            'bio_full' => 'Prince architects and builds the systems that keep The Atelier Noir running — from the storefront and checkout to the internal tools the team relies on day to day. His focus is making sure every part of the platform feels as considered as the products it sells: fast, stable, and quietly out of the way of the experience itself. He works closely with the design side of the team to translate the brand\'s Greek-inspired visual language into code without losing any of its refinement.',
            'skills'   => ['Full-Stack Development', 'PHP &amp; MySQL', 'System Architecture', 'API Integration'],
        ],
    ],

    'team' => [
        [
            'slug'     => 'mark-vertudez',
            'name'     => 'Mark Andrei Vertudez',
            'role'     => 'Professional Marketing Strategist',
            'initials' => 'MV',
            'image'    => '',
            'bio'      => 'Shapes the strategies that connect The Atelier Noir with its audience. Mark focuses on positioning, marketing direction, and creating meaningful opportunities for the brand to grow.',
            'bio_full' => 'Mark builds the strategy that carries The Atelier Noir\'s voice beyond the storefront — campaign direction, brand positioning, and the timing behind every collection launch. He works to make sure growth never comes at the cost of the brand\'s identity, treating every marketing decision as an extension of the same philosophy that shapes the product itself.',
            'skills'   => ['Marketing Strategy', 'Brand Positioning', 'Campaign Planning', 'Audience Growth'],
        ],
        [
            'slug'     => 'ralf-arenas',
            'name'     => 'Ralf A. Arenas',
            'role'     => 'Feature &amp; Product Analyzer',
            'initials' => 'RA',
            'image'    => 'https://scontent.fmnl45-1.fna.fbcdn.net/v/t39.30808-1/671065960_1475032844296349_6512508870489660141_n.jpg?stp=dst-jpg_tt6&cstp=mx1080x1101&ctp=p100x100&_nc_cat=105&_nc_map=urlgen_bucketless&ccb=1-7&_nc_sid=e99d92&_nc_eui2=AeH-f3Kw6ZNNjCRsXIceHONQXvxeBikCGABe_F4GKQIYAMM_dFJeBGxJXi1Qgessskz3q67qmOp5m3JAHNRrQ994&_nc_ohc=oOVXxdru7XYQ7kNvwFOoC3E&_nc_oc=AdqmnduJG_FHRGJSBIUip6faAmerVCUj5ON0YIFiTMhYpKd20EK8v3fIFVnEotZMF_ZIkzPcZZ4XvdTUjVHxQab_&_nc_zt=24&_nc_ht=scontent.fmnl45-1.fna&_nc_gid=WJA2T1m72tyRkr3DwuKXyw&_nc_ss=7b2a8&oh=00_AQF0r8mFK6H8CdOT8uju07p7egGiJn3pBlnd1O0dfcOocA&oe=6A853368',
            'bio'      => 'Examines products and platform features with a focus on functionality, relevance, and customer experience. Ralf helps ensure that every feature and offering provides meaningful value.',
            'bio_full' => 'Ralf studies how the platform is actually used — which features earn their place and which quietly get in the way. He evaluates every new addition to the site and the collection against a single question: does this genuinely improve the client\'s experience. That process keeps The Atelier Noir\'s offerings deliberate rather than reactive.',
            'skills'   => ['Product Analysis', 'Feature QA', 'UX Research', 'Requirements Planning'],
        ],
        [
            'slug'     => 'ckiel-eronico',
            'name'     => 'Ckiel Abraham Eronico',
            'role'     => 'Social Media Strategist &amp; Client Communicator',
            'initials' => 'CE',
            'image'    => 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/includes/images/a8ab73fd-8d66-485f-b012-39b097a141fb%20(1).jpeg',
            'bio'      => 'Strengthens the connection between The Atelier Noir and its community. Ckiel develops social media strategies while maintaining clear and meaningful communication with clients and audiences.',
            'bio_full' => 'Ckiel manages how The Atelier Noir shows up outside its own platform — social presence, community tone, and direct client communication. He treats every reply and every post as part of the same client experience the site is designed around, keeping the brand\'s voice consistent wherever people encounter it.',
            'skills'   => ['Social Media Strategy', 'Client Communications', 'Content Planning', 'Community Management', 'Clothing &amp; Fashion Expertise'],
        ],
        [
            'slug'     => 'katherine-gesmundo',
            'name'     => 'Katherine Jasmine Gesmundo',
            'role'     => 'UI/UX Designer',
            'initials' => 'KG',
            'image'    => '',
            'bio'      => 'Transforms ideas into intuitive and visually refined digital experiences. Katherine focuses on creating interfaces that reflect the elegance of The Atelier Noir while keeping every interaction simple and engaging.',
            'bio_full' => 'Katherine designs the interfaces clients actually touch — layouts, flows, and the small interaction details that make browsing feel effortless. She works to keep every screen aligned with the brand\'s Greek-inspired elegance while never letting that aesthetic get in the way of clarity or ease of use.',
            'skills'   => ['UI/UX Design', 'Prototyping', 'Design Systems', 'Interaction Design'],
        ],
    ],

];