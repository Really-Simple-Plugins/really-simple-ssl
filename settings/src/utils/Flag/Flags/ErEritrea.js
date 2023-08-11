import * as React from "react";
const SvgErEritrea = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={17}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="ER_-_Eritrea_svg__a"
      width={17}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h17v12H0z" />
    </mask>
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#ER_-_Eritrea_svg__a)">
      <path fill="#43B764" d="M0 0v6h16V0H0Z" />
      <path fill="#B4D7F4" d="M0 6v6h16V6H0Z" />
      <path fill="#BE0027" d="m0 0 16.5 6L0 12V0Z" />
      <path
        fill="#F3E294"
        d="m4.557 8.684-.477.01a4.742 4.742 0 0 1-.096-.128 3.157 3.157 0 0 1-.355-1.458v.129c-.002.582-.002.828.355 1.33.102.197.225.39.37.58l.616-.473-.413.01Z"
      />
      <path
        fill="#F3E294"
        d="M1 6.25a3.25 3.25 0 1 0 6.5 0 3.25 3.25 0 0 0-6.5 0Zm5.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"
      />
    </g>
  </svg>
);
export default SvgErEritrea;
