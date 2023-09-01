import * as React from "react";
const SvgMvMaldives = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="MV_-_Maldives_svg__a"
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
    <g mask="url(#MV_-_Maldives_svg__a)">
      <path
        fill="#C51918"
        fillRule="evenodd"
        d="M0 0h16v11a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#C51918"
        fillRule="evenodd"
        d="M0 0h16v12H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#579D20"
        fillRule="evenodd"
        d="M3 3h10v6H3V3Z"
        clipRule="evenodd"
      />
      <path stroke="#B6EB9A" strokeOpacity={0.238} d="M3.5 3.5h9v5h-9v-5Z" />
      <mask
        id="MV_-_Maldives_svg__b"
        width={10}
        height={6}
        x={3}
        y={3}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M3 3h10v6H3V3Z"
          clipRule="evenodd"
        />
        <path stroke="#fff" d="M3.5 3.5h9v5h-9v-5Z" />
      </mask>
      <g mask="url(#MV_-_Maldives_svg__b)">
        <path
          fill="#F9FAFA"
          fillRule="evenodd"
          d="M8.016 6.231c-.008 1.533 1.199 2.35 1.199 2.35-1.377.162-2.293-1.086-2.293-2.335 0-1.248 1.248-2.28 2.293-2.745 0 0-1.19 1.197-1.199 2.73Z"
          clipRule="evenodd"
        />
      </g>
    </g>
  </svg>
);
export default SvgMvMaldives;
