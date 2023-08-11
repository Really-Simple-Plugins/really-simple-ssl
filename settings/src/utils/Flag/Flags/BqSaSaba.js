import * as React from "react";
const SvgBqSaSaba = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="BQ-SA_-_Saba_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#BQ-SA_-_Saba_svg__a)">
      <path fill="#fff" d="M0 0h16v12H0V0Z" />
      <path fill="#F00000" d="M0 5.832V0h8L0 5.832ZM16 5.832V0H8l8 5.832Z" />
      <path
        fill="#00268D"
        d="M0 5.832V12h8L0 5.832ZM16 5.832v6.336L8 12l8-6.168Z"
      />
      <path
        fill="#FEDA00"
        d="M7.857 7.128 6.098 8.382l.646-2.071L5 5.043h2.172L7.857 3l.726 2.043h2.113L8.967 6.311l.657 2.07-1.767-1.253Z"
      />
    </g>
  </svg>
);
export default SvgBqSaSaba;
